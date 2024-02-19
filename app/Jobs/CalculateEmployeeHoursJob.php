<?php

namespace App\Jobs;

use App\Services\CalculateEmployeeHoursService;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateEmployeeHoursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 1;

    protected $date;
    protected $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $email, $logId = null)
    {
        $this->date = $date;
        $this->email = $email;
        $this->logId = $logId;
        $this->subject = 'Calculate Employee Hours: '.$this->date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            CalculateEmployeeHoursService::handle(Carbon::parse($this->date));
            $this->markLogAsSuccess();
        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }
}
