<?php

namespace App\Jobs;

use App\Mail\DailyProgressEmail;
use App\Models\DialerAgent;
use App\Models\DialerAgentEvaluation;
use App\Models\DialerAgentType;
use App\Models\DialerAgentWriteup;
use App\Models\DialerTeam;
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

class SendDailyProgressEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

    protected CarbonImmutable $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $logId = null)
    {
        $this->date = $date;
        $this->logId = $logId;
        $this->subject = 'Daily Progress Report: '.$this->date->format('n/j/Y');
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
            $weekStart = $this->date->startOfWeek()->startOfDay();
            $weekEnd = $this->date->endOfWeek()->endOfDay();

            $stats = collect([]);

            $teams = DialerTeam::query()
                ->get();

            $teams->each(function ($team) use ($weekStart, $weekEnd, &$stats) {

                $agents = DialerAgent::query()
                    ->isActiveForDateRange($weekStart, $weekEnd)
                    ->where('team_id', $team->id)
                    ->where('agent_type_id', DialerAgentType::AGENT)
                    ->get();

                $weekly_completed_evaluations = DialerAgentEvaluation::query()
                    ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_evaluations.agent_id')
                    ->whereBetween('dialer_agent_evaluations.created_at', [
                        $weekStart,
                        $weekEnd,
                    ])
                    ->where('dialer_agents.team_id', $team->id)
                    ->groupBy('dialer_agents.id')
                    ->get();

                $weekly_completed_writeups = DialerAgentWriteup::query()
                    ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_writeups.agent_id')
                    ->whereBetween('dialer_agent_writeups.date', [
                        $weekStart,
                        $weekEnd,
                    ])
                    ->where('dialer_agents.team_id', $team->id)
                    ->groupBy('dialer_agents.id')
                    ->get();

                $stats->push([
                    'team' => $team,
                    'active_agents' => $agents->count(),
                    'evaluations_completed_count' => $weekly_completed_evaluations->count(),
                    'evaluations_completed_percent' => $weekly_completed_evaluations->count() > 0 ? round(($weekly_completed_evaluations->count() / $agents->count()) * 100) : 0,
                    'evaluations_expected_percent' => $this->date->dayOfWeekIso * 20,
                    'writeups_completed_count' => $weekly_completed_writeups->count(),
                ]);
            });

            $daily_completed_evaluations = DialerAgentEvaluation::query()
                ->whereDate('dialer_agent_evaluations.created_at', $this->date)
                ->get();

            $daily_completed_writeups = DialerAgentWriteup::query()
                ->whereDate('dialer_agent_writeups.created_at', $this->date)
                ->get();


            $this->markLogAsSuccess();

            Mail::send(new DailyProgressEmail(
                date: $this->date,
                subject_line: $this->subject,
                data: [
                    'startDate' => $weekStart,
                    'endDate' => $weekEnd,
                    'stats' => $stats,
                    'evaluations_completed' => $daily_completed_evaluations,
                    'writeups_completed' => $daily_completed_writeups,
                ],
            ));
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
