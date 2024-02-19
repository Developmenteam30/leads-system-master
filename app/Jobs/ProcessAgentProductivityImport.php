<?php

namespace App\Jobs;

use App\External\Convoso\APIRequest;
use App\External\Convoso\Convoso;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentPerformance;
use App\Models\DialerExternalCampaign;
use App\Models\DialerNotificationType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAgentProductivityImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;
    protected $logId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected CarbonImmutable $date,
    ) {
        $this->subject = 'Agent Productivity Import: '.$this->date->format('n/j/Y');

        $log = new AuditLog();
        $log->action = 'UPLOAD:DIALER-AGENT-PRODUCTIVITY';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'file' => 'API Import: '.$this->date->format('Y-m-d'),
            'file_date' => $this->date->format('Y-m-d'),
        ]);
        $log->save();
        $this->logId = $log->logId;
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
            DB::beginTransaction();

            // Clear out existing records
            DialerAgentPerformance::query()
                ->whereDate('dialer_agent_performances.file_date', $this->date)
                ->update([
                    'coaching_time' => null,
                    'huddle_time' => null,
                ]);

            $cnt = 0;
            $convoso = new APIRequest();
            try {

                $campaigns = DialerExternalCampaign::query()
                    ->select([
                        'campaign_id',
                        DB::raw('GROUP_CONCAT(id) as campaign_ids'),
                    ])
                    ->groupBy('campaign_id')
                    ->get();

                $campaigns->each(function ($campaign) use ($convoso, &$cnt) {
                    $agents = [];
                    $limit = 1000;
                    $offset = 0;

                    do {
                        $rows = $convoso->getAgentProductivity([
                            'date_start' => $this->date->setTime(0, 0)->toDateTimeLocalString(),
                            'date_end' => $this->date->setTime(23, 59, 59)->toDateTimeLocalString(),
                            'campaign_id' => $campaign->campaign_ids,
                            'limit' => $limit,
                            'offset' => $offset,
                        ]);

                        foreach ($rows->entries as $row) {
                            // 6984: Exclude non-integer agents from being imported via the API.
                            if (!preg_match('/^[0-9]/', $row->user_name)) {
                                //Log::debug("Excluding {$row->user_name}");
                                continue;
                            }

                            if (!in_array($row->availability_code, [
                                Convoso::AVAILABILITY_COACHING,
                                Convoso::AVAILABILITY_HUDDLE,
                                Convoso::FORCED_PAUSE,
                                Convoso::SYSTEM_FORCED_LOGOUT,
                            ])) {
                                continue;
                            }

                            $agent = DialerAgent::firstOrCreate(
                                [
                                    'id' => $row->user_id,
                                ],
                                [
                                    'agent_name' => $row->user_name,
                                ]
                            );
                            if ($agent->wasRecentlyCreated) {
                                DialerAgentEffectiveDate::createDefaultEntry($agent, $this->date->format('Y-m-d'));
                            }
                            $agent->save();

                            $effectiveDateRow = $agent->getEffectiveValuesForDate($this->date);
                            if (empty($effectiveDateRow)) {
                                $effectiveDateRow = DialerAgentEffectiveDate::createDefaultEntry($agent, $this->date->format('Y-m-d'));
                            }

                            // Initialize the array if not already set
                            if (!isset($agents[$row->user_id])) {
                                $agents[$row->user_id] = [
                                    'coaching_time' => 0,
                                    'huddle_time' => 0,
                                    'forced_pause_cnt' => 0,
                                    'forced_logout_cnt' => 0,
                                ];
                            }

                            $time = !empty($row->event_sec) ? Carbon::parse($row->event_sec)->secondsSinceMidnight() : 0;

                            switch ($row->availability_code) {
                                case Convoso::AVAILABILITY_COACHING:
                                    // 7474: Exclude coaching that is under 1 minute
                                    // 7683: Exclude coaching that is under 2 minutes
                                    if ($time >= 120) {
                                        $agents[$row->user_id]['coaching_time'] += $time;
                                    }
                                    break;

                                case Convoso::AVAILABILITY_HUDDLE:
                                    // 7606: They get up to 15 minutes of huddle time per day, but cap it at 9am ET.
                                    $startTime = CarbonImmutable::parse($row->created_at, config('convoso.timezone'))->timezone(config('settings.timezone.local'));
                                    $endTime = $startTime->addSeconds($time);

                                    if ($startTime->lt($startTime->setTime(9, 0))) {
                                        $agents[$row->user_id]['huddle_time'] += $startTime->diffinSeconds($endTime->min($endTime->setTime(9, 0)));
                                    }
                                    break;

                                case Convoso::FORCED_PAUSE:
                                    $agents[$row->user_id]['forced_pause_cnt']++;
                                    break;

                                case Convoso::SYSTEM_FORCED_LOGOUT:
                                    $agents[$row->user_id]['forced_logout_cnt']++;
                                    break;
                            }

                            $cnt++;
                        }

                        $offset += $limit;

                    } while (sizeof($rows->entries) >= $limit);

                    foreach ($agents as $agent_id => $values) {
                        $record = DialerAgentPerformance::updateOrCreate(
                            [
                                'agent_id' => $agent_id,
                                'file_date' => $this->date->format('Y-m-d'),
                                'internal_campaign_id' => $campaign->campaign_id,
                            ],
                            [
                                'coaching_time' => $values['coaching_time'],
                                'huddle_time' => min(15 * 60, $values['huddle_time']), // 7606: They get up to 15 minutes of huddle time per day, but cap it at 9am ET.
                                'forced_pause_cnt' => $values['forced_pause_cnt'],
                                'forced_logout_cnt' => $values['forced_logout_cnt'],
                            ]
                        );
                        $record->save();
                    }
                });

            } catch (\Exception $e) {
                DB::rollback();

                Log::error(self::class." Exception: {$e->getMessage()}");
                $this->fail($e);

                return;
            }

            $this->markLogAsSuccess();

            DB::commit();

            // Only send on the weekends if there is something to report.
            if ($cnt > 0 || ($this->date->dayOfWeek >= CarbonInterface::MONDAY && $this->date->dayOfWeek <= CarbonInterface::FRIDAY)) {
                Mail::to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                    ->send(new JobStatus(
                        jobType: self::class,
                        status: 'Success',
                        file: 'API Import: '.$this->date->format('Y-m-d'),
                        subject: $this->subject,
                        rowCount: $cnt,
                    ));
            }
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
