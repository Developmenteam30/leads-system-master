<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobStatus extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public $jobType = null,
        public $status = null,
        public $file = null,
        public $subject = null,
        public $rowCount = null,
        public $error = null,
        public $successCount = null,
        public $failCount = null,
        public $newUsers = [],
        public $updatedUsers = [],
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("Job Status: {$this->subject}")
            ->markdown("emails.job-status", [
                'jobType' => $this->jobType,
                'status' => $this->status,
                'file' => $this->file,
                'rowCount' => $this->rowCount,
                'successCount' => $this->successCount,
                'failCount' => $this->failCount,
                'error' => $this->error,
                'newUsers' => $this->newUsers,
                'updatedUsers' => $this->updatedUsers,
            ]);
    }
}
