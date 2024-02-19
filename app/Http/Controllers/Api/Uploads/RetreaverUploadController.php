<?php

namespace App\Http\Controllers\Api\Uploads;

use App\Jobs\ProcessRetreaverFileJob;
use App\Models\AuditLog;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RetreaverUploadController extends BaseController
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
                    'Documentable Type' => 'Retreaver Log',
                ]);

                $name = basename($upload->getOriginalName(), '.part');
                $time = hrtime(true);
                $newPath = Storage::disk('local')->path("uploads/{$time}-{$name}");

                File::move($upload->getPath(), $newPath);

                $log = AuditLog::createFromRequest($request, ProcessRetreaverFileJob::ACTION_NAME, [
                    'file' => $newPath,
                    'file_date' => $request->input('file_date'),
                ]);

                ProcessRetreaverFileJob::dispatch(
                    file: $newPath,
                    email: $request->user()->email,
                    logId: $log->logId,
                    date: CarbonImmutable::parse($request->input('file_date')),
                );
            }

            return response([]);
        } catch (\Exception $e) {
            return ErrorResponse::json('Upload error: '.$e->getMessage(), 400);
        }
    }
}
