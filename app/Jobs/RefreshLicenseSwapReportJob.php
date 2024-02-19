<?php

namespace App\Jobs;

use App\Mail\LicenseSwapReportEmail;
use App\Models\DialerReportLicenseSwap;
use App\Services\RefreshLicenseSwapReportService;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RefreshLicenseSwapReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected CarbonImmutable $date,
        protected $logId = null,
    ) {
        $this->subject = 'License Swap Report: '.$this->date->format('n/j/Y');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

        try {
            RefreshLicenseSwapReportService::handle($this->date);

            Mail::send(new LicenseSwapReportEmail(
                date: $this->date,
                subject_line: $this->subject,
                data: DialerReportLicenseSwap::first()->data,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
