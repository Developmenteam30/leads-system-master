<?php

namespace App\Http\Controllers\Api\Uploads;

use App\Jobs\DocumentUploadEmailJob;
use App\Models\DialerAgent;
use App\Models\DialerDocument;
use App\Models\DialerLeaveRequest;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentUploadController extends UploadController
{
    /**
     * Store an agent document.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function agent(Request $request, $agent_id)
    {
        ApiJsonValidator::validate(array_merge($request->all(), $request->route()->parameters()), [
            'agent_id' => 'required|bail|exists:dialer_agents,id',
        ]);

        $final_directory = "documents/agents/{$agent_id}/";

        return self::store($request, DialerAgent::class, $agent_id, $final_directory);
    }

    /**
     * Store a leave request document.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function leave_request(Request $request, $id)
    {
        ApiJsonValidator::validate(array_merge($request->all(), $request->route()->parameters()), [
            'id' => 'required|bail|exists:dialer_leave_requests,id',
        ]);

        $final_directory = "documents/leave-requests/{$id}/";

        return self::store($request, DialerLeaveRequest::class, $id, $final_directory);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request, $documentable_type, $documentable_id, $final_directory)
    {
        ApiJsonValidator::validate(array_merge($request->all(), $request->route()->parameters()), [
            'file' => 'bail|required',
            'unique_id' => 'bail|required|uuid',
            'document_type_id' => 'bail|required|exists:dialer_document_types,id',
        ]);

        try {
            $upload = new UploadController($request);
            $upload->appendChunk();

            if ($request->has('is_last') && $request->boolean('is_last')) {

                $upload->checkMimeType([
                    'application/msword',
                    'application/pdf',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
                    'image/bmp',
                    'image/gif',
                    'image/jpeg',
                    'image/png',
                    'image/tiff',
                    'text/csv',
                    'text/plain',
                ], 'Accepted file types: Text File, Word Document, Excel Document, PDF, Image File (PNG, JPEG, GIF, BMP, TIFF)');

                $upload->virusScan([
                    'Documentable ID' => $documentable_id,
                    'Documentable Type' => $documentable_type,
                ]);

                $newPath = "{$final_directory}{$upload->getHashName()}";
                Storage::disk('s3')->put($newPath, fopen($upload->getPath(), 'r+'));

                $document = DialerDocument::create([
                    'title' => $upload->getOriginalName(),
                    'file_path' => $newPath,
                    'documentable_id' => $documentable_id,
                    'documentable_type' => $documentable_type,
                    'document_type_id' => $request->input('document_type_id'),
                ]);

                DocumentUploadEmailJob::dispatch(
                    document: $document,
                );
            }

            return response([]);
        } catch (\Exception $e) {
            return ErrorResponse::json('Upload error: '.$e->getMessage(), 400);
        }
    }
}
