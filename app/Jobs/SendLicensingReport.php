<?php

namespace App\Jobs;

use App\Mail\LicensingReportMail;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLicensingReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const REPORT_HOUR = 15;
    const REPORT_MINUTE = 40;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

    protected Carbon $date;
    protected $file = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $logId = null)
    {
        $this->date = $date;
        $this->logId = $logId;
        $this->subject = 'Licensing Report: '.$this->date->format('Y-m-d');
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

            // Logs are in UTC
            $yesterdayUTC = $this->date->clone()->subDay()->setTime(self::REPORT_HOUR, self::REPORT_MINUTE)->setTimezone(new \DateTimeZone('UTC'));
            $todayUTC = $this->date->clone()->setTime(self::REPORT_HOUR, self::REPORT_MINUTE)->setTimezone(new \DateTimeZone('UTC'));

            $agents_touched = AuditLog::query()
                ->select('notes->oldValues->agent_id')
                ->whereIn('action', ['DIALER-AGENT-EFFECTIVE-DATE:SAVE', 'App\Models\DialerAgentEffectiveDate:UPDATE'])
                ->whereBetween('timestamp', [
                    $yesterdayUTC,
                    $todayUTC,
                ])
                ->whereNotNull('notes->oldValues->agent_id')
                ->whereColumn('notes->oldValues->end_date', '<>', 'notes->newValues->end_date');

            $agents = DialerAgent::query()
                ->with([
                    'latestActiveEffectiveDate',
                ])
                ->select([
                    'dialer_agents.id',
                    'dialer_agents.agent_name',
                ])
                ->whereHas('latestActiveEffectiveDate', function (Builder $query) use ($agents_touched) {
                    // Look for anyone with a termination date of today OR whose record was edited today with a termination date before today
                    $query->whereDate('end_date', '=', $this->date)
                        ->orWhere(function (Builder $query) use ($agents_touched) {
                            $query->whereDate('end_date', '<=', $this->date)
                                ->whereIn('dialer_agents.id', $agents_touched);
                        });
                })
                ->whereHas('latestActiveEffectiveDate', function (Builder $query) {
                    $query->where('agent_type_id', DialerAgentType::AGENT);
                })
                ->get();

            // Only send on the weekends if there is something to report.
            if ($agents->count() > 0 || ($this->date->dayOfWeek >= 1 && $this->date->dayOfWeek <= 5)) {
                Mail::send(new LicensingReportMail($this->date, $agents));
            }
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
