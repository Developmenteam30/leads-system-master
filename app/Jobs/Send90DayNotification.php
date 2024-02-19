<?php

namespace App\Jobs;

use App\Mail\R90DayNotificationMail;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Send90DayNotification implements ShouldQueue
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
    public function __construct(protected CarbonImmutable $date, protected $logId = null)
    {
        $this->subject = '90-Day Anniversaries: '.$this->date->format('n/j/Y');
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
            $next_week_monday = $this->date->next('Monday');
            $next_week_sunday = $next_week_monday->next('Sunday');

            $agents = DialerAgentEffectiveDate::query()
                ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_effective_dates.agent_id')
                ->where('dialer_agents.agent_name', 'NOT LIKE', 'System%')
                ->where('dialer_agent_effective_dates.is_training', '=', '1')
                ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
                ->whereNull('dialer_agent_effective_dates.end_date')
                ->whereBetween(DB::raw('DATE_ADD(start_date,INTERVAL 90 DAY)'), [
                    $next_week_monday->format('Y-m-d'),
                    $next_week_sunday->format('Y-m-d'),
                ])
                ->get();

            Mail::send(new R90DayNotificationMail(
                next_week_monday: $next_week_monday,
                next_week_sunday: $next_week_sunday,
                agents: $agents,
            ));

            $this->markLogAsSuccess();
        } catch (\Exception $e) {
            //Exception
            echo $e->getMessage();
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
