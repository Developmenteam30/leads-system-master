<?php

namespace App\Jobs;

use App\Mail\MissingDispoReportMail;
use App\Models\DialerHoliday;
use App\Models\DialerHolidayList;
use App\Models\DialerLog;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMissingDispoReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const REPORT_HOUR = 11;
    const REPORT_MINUTE = 0;

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
        protected $logId,
    ) {
        $this->subject = 'Missing Disposition Report: '.$this->date->format('Y-m-d');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $this->markLogAsSuccess();

            $missing = [];

            $period = CarbonPeriod::between($this->date->subWeeks(2), $this->date->subDay(1));
            $weekendFilter = function ($date) {

                // Skip weekends and US holidays
                $holiday = DialerHoliday::query()
                    ->whereDate('holiday', $date)
                    ->whereHas('holidayLists', function (Builder $query) {
                        $query->where('holiday_list_id', DialerHolidayList::US_ID);
                    })
                    ->exists();

                return $date->isWeekday() && !$holiday;
            };
            $period->filter($weekendFilter);

            foreach ($period as $date) {

                $dispo = DialerLog::query()
                    ->whereNotNull( 'year')
                    ->timestampQuery($date->format('Y-m-d'))
                    ->count();

                Log::debug(self::class." {$date->format('Y-m-d')} {$dispo}");

                // Use a threshold of 25,000 calls
                if (empty($dispo) || $dispo < 25000) {
                    $missing[] = $date->format('Y-m-d');
                }
            };

            // Only send if there is something to report.
            if (!empty($missing)) {
                Mail::send(new MissingDispoReportMail($this->date, $missing));
            }
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
