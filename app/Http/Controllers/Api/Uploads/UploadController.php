<?php

namespace App\Http\Controllers\Api\Uploads;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Sineflow\ClamAV\Scanner;
use Sineflow\ClamAV\ScanStrategy\ScanStrategyClamdNetwork;

class UploadController
{
    private array|UploadedFile|null $fragment;
    private string $path;

    /**
     * @throws Exception
     */
    public function __construct(private Request $request)
    {
        $this->fragment = $this->request->file('file');
        if (!$this->fragment->isValid()) {
            throw new Exception("Upload error: {$this->fragment->getErrorMessage()}");
        }

        $this->path = Storage::disk('local')->path("chunks/{$this->request->input('unique_id')}.part");
    }

    public function getHashName(): string
    {
        return $this->fragment->hashName();
    }

    public function getOriginalName(): string
    {
        return $this->fragment->getClientOriginalName();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Append the uploaded chunk
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function appendChunk(): void
    {
        File::append($this->path, $this->fragment->get());
    }

    /**
     * Perform a virus scan on an uploaded file
     *
     * @param  string  $path
     * @param  array|UploadedFile|UploadedFile[]|null  $fragment
     * @param  Request  $request
     * @param  array  $log_details
     * @throws Exception
     */
    public function virusScan(array $log_details): void
    {
        if (!App::environment('local')) {
            $scanner = new Scanner(new ScanStrategyClamdNetwork('localhost', 3310));
            $scannedFile = $scanner->scan($this->path);
            if (!$scannedFile->isClean()) {
                Log::stack(['single', 'slack'])->notice('Potential virus found in file', array_merge([
                    'Filename' => $this->fragment->getClientOriginalName(),
                    'Virus' => $scannedFile->getVirusName(),
                    'IP Address' => $this->request->ip() ?? null,
                    'Logged In User' => $this->request->user()->id ?? null,
                    'Logged In Name' => $this->request->user()->agent_name ?? null,
                ], $log_details));

                throw new Exception("Potential virus found in file: {$scannedFile->getVirusName()}");
            }
        }
    }

    /**
     * Check the MIME type of the uploaded file against the allow list
     *
     * @param  array  $allowed_types
     * @param  string  $error_string
     * @throws Exception
     */
    public function checkMimeType(array $allowed_types, string $error_string): void
    {
        $file = new UploadedFile($this->path, $this->fragment->getClientOriginalName());
        if (!in_array($file->getMimeType(), $allowed_types)) {
            throw new Exception($error_string);
        }
    }
}
