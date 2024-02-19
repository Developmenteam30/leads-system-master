<?php

namespace App\Jobs;

use App\Mail\WriteupAgentNotificationMail;
use App\Models\DialerAgentWriteup;
use App\Traits\FailedJobTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWriteupNotificationAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait;

    protected DialerAgentWriteup $writeup;

    /**
     * Create a new job instance.
     */
    public function __construct(DialerAgentWriteup $writeup)
    {
        $this->writeup = $writeup;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::send(new WriteupAgentNotificationMail($this->writeup));
            $this->markLogAsSuccess();
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}

