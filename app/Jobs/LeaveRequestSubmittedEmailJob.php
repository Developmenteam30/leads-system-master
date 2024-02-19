<?php

namespace App\Jobs;

use App\Mail\LeaveRequestSubmittedEmail;
use App\Models\DialerLeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeaveRequestSubmittedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private DialerLeaveRequest $request,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send(new LeaveRequestSubmittedEmail(
                request: $this->request,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
