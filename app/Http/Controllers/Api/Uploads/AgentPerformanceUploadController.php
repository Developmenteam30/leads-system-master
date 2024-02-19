<?php

namespace App\Http\Controllers\Api\Uploads;

use App\Jobs\ProcessAgentPerformanceFile;
use App\Models\AuditLog;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AgentPerformanceUploadController extends BaseController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        ApiJsonValidator::validate($request->all(), [
            'file' => 'bail|required',
            'file_date' => 'required|bail|date',
            'unique_id' => 'bail|required|uuid',
        ]);

        try {
            $upload = new UploadController($request);
            $upload->appendChunk();

            if ($request->has('is_last') && $request->boolean('is_last')) {

                $upload->checkMimeType([
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                    'text/csv',
                ], 'Accepted file types: Excel Document or CSV file');

                $upload->virusScan([
                    'Documentable Type' => 'Dialer Agent Performance',
                ]);

                $name = basename($upload->getOriginalName(), '.part');
                $time = hrtime(true);
                $newPath = Storage::disk('local')->path("uploads/{$time}-{$name}");

                File::move($upload->getPath(), $newPath);

                $log = AuditLog::createFromRequest($request, 'UPLOAD:DIALER-AGENT-PERFORMANCE', [
                    'file' => $newPath,
                    'file_date' => $request->input('file_date'),
                ]);

                ProcessAgentPerformanceFile::dispatch(
                    file: $newPath,
                    date: $request->input('file_date'),
                    email: $request->user()->email,
                    logId: $log->logId,
                );
            }

            return response([]);
        } catch (\Exception $e) {
            return ErrorResponse::json('Upload error: '.$e->getMessage(), 400);
        }
    }
}
