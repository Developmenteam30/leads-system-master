<?php

namespace App\Traits;

use App\Mail\JobStatus;
use App\Models\DialerNotificationType;
use Illuminate\Support\Facades\Mail;

trait FailedJobTrait
{
    use JobStatusUpdateAuditLogTrait;

    protected $email;
    protected $file;
    protected string $subject;

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        print $exception->getMessage().PHP_EOL.PHP_EOL;
        print $exception->getTraceAsString().PHP_EOL.PHP_EOL;

        $this->markLogAsFailure($exception);

        Mail::to($this->email)
            ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
            ->send(new JobStatus(
                jobType: self::class,
                status: 'Failure',
                file: !empty($this->file) ? basename($this->file) : null,
                subject: $this->subject,
                error: $exception->getMessage(),
            ));
    }
}
