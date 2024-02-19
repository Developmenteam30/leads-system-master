<?php

namespace App\Jobs;

use App\Mail\DocumentUploadEmail;
use App\Models\DialerDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DocumentUploadEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private DialerDocument $document,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send(new DocumentUploadEmail(
                document: $this->document,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
