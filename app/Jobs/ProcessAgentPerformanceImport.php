<?php

namespace App\Jobs;

use App\External\Convoso\APIRequest;
use App\Mail\JobStatus;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentPerformance;
use App\Models\DialerExternalCampaign;
use App\Models\DialerNotificationType;
use App\Services\CalculateEmployeeHoursService;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAgentPerformanceImport implements ShouldQueue
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
        protected $date = null,
        protected $sendStatsReport = false,
        protected $logId = null,
        protected $email = null,
    ) {
        $this->subject = 'Agent Hours Import: '.$this->date;
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
            $newUsers = $updatedUsers = [];

            DB::beginTransaction();

            // Clear out existing records
            DialerAgentPerformance::query()
                ->whereDate('dialer_agent_performances.file_date', $this->date)
                ->update([
                    'wait_pct' => null,
                    'calls' => null,
                    'contacts' => null,
                    'talk_pct' => null,
                    'pause_pct' => null,
                    'wrapup_pct' => null,
                    'talk_time' => null,
                    'wait_time' => null,
                    'pause_time' => null,
                    'wrapup_time' => null,
                    'total_time' => null,
                    'net_time' => null,
                    'billable_time' => null,
                    'billable_time_override' => null,
                    'voicemail' => null,
                    'others' => null,
                    'voicemail_pct' => null,
                    'others_pct' => null,
                    'payable_training' => 0,
                    'billable_training' => 0,
                    'payable_rate' => null,
                    'billable_rate' => null,
                ]);

            $cnt = 0;
            try {
                $convoso = new APIRequest();

                $campaigns = DialerExternalCampaign::query()
                    ->select([
                        'campaign_id',
                        DB::raw('GROUP_CONCAT(id) as campaign_ids'),
                    ])
                    ->groupBy('campaign_id')
                    ->get();

                $campaigns->each(function ($campaign) use (&$agents, $convoso, &$cnt, &$newUsers, &$updatedUsers) {

                    $rows = $convoso->getAgentPerformance([
                        'date_start' => $this->date->setTime(0, 0)->toDateTimeLocalString(),
                        'date_end' => $this->date->copy()->setTime(23, 59, 59)->toDateTimeLocalString(),
                        'campaign_ids' => $campaign->campaign_ids,
                    ]);

                    foreach ($rows as $row) {
                        // 6984: Exclude non-integer agents from being imported via the API.
                        if (!preg_match('/^[0-9]/', $row->name)) {
                            //Log::debug("Excluding {$row->name}");
                            continue;
                        }

                        $agent = DialerAgent::firstOrCreate(
                            [
                                'id' => $row->user_id,
                            ],
                            [
                                'agent_name' => $row->name,
                            ]
                        );
                        if ($agent->wasRecentlyCreated) {
                            DialerAgentEffectiveDate::createDefaultEntry($agent, $this->date);
                            $newUsers[] = "{$row->user_id} - {$row->name}";
                        }
                        $agent->save();

                        $effectiveDateRow = $agent->getEffectiveValuesForDate($this->date);
                        if (empty($effectiveDateRow)) {
                            DialerAgentEffectiveDate::createDefaultEntry($agent, $this->date);
                            $updatedUsers[] = "{$row->user_id} - {$row->name}";
                        }

                        $values = [
                            'wait_pct' => $row->wait_sec_pt ?? null,
                            'calls' => $row->calls ?? null,
                            'contacts' => $row->human_answered ?? null,
                            'talk_pct' => $row->talk_sec_pt ?? null,
                            'pause_pct' => $row->pause_sec_pt ?? null,
                            'wrapup_pct' => $row->wrap_sec_pt ?? null,
                            'talk_time' => !empty($row->talk_sec) ? Carbon::parse($row->talk_sec)->secondsSinceMidnight() : null,
                            'wait_time' => !empty($row->wait_sec) ? Carbon::parse($row->wait_sec)->secondsSinceMidnight() : null,
                            'pause_time' => !empty($row->pause_sec) ? Carbon::parse($row->pause_sec)->secondsSinceMidnight() : null,
                            'wrapup_time' => !empty($row->wrap_sec) ? Carbon::parse($row->wrap_sec)->secondsSinceMidnight() : null,
                            'total_time' => !empty($row->total_time) ? Carbon::parse($row->total_time)->secondsSinceMidnight() : null,
                            'voicemail' => $row->am ?? null,
                            'voicemail_pct' => $row->am_pt ?? null,
                            'others' => $row->others ?? null,
                            'others_pct' => $row->others_pt ?? null,
                        ];
                        $values['net_time'] = ($values['talk_time'] ?? 0) + ($values['wait_time'] ?? 0) + ($values['wrapup_time'] ?? 0);
                        $values['billable_time'] = floor($values['net_time'] / 60); // We only care about full minutes
                        // 7362: No more break time, effective 5/15/23
                        if ($this->date->lt(Carbon::parse('2023-05-15 00:00:00'))) {
                            if ($values['billable_time'] > 240) {
                                $values['billable_time'] += 30;
                            } else {
                                $values['billable_time'] += 15;
                            }
                        }
                        $values['billable_time_override'] = $values['billable_time'];

                        $record = DialerAgentPerformance::updateOrCreate(
                            [
                                'agent_id' => $agent->id,
                                'file_date' => $this->date->format('Y-m-d'),
                                'internal_campaign_id' => $campaign->campaign_id,
                            ],
                            $values
                        );
                        $record->save();

                        $cnt++;
                    }

                });
            } catch (\Exception $e) {
                DB::rollback();

                Log::error(self::class." Exception: {$e->getMessage()}");
                $this->fail($e);

                return;
            }

            ProcessAgentProductivityImport::dispatch(
                date: CarbonImmutable::parse($this->date),
            );
            CalculateEmployeeHoursService::handle(Carbon::parse($this->date));
            if ($cnt > 0 && $this->sendStatsReport) {
                SendStatsReport::dispatch(date: $this->date);
            }

            $this->markLogAsSuccess();

            DB::commit();

            // Only send on the weekends if there is something to report.
            if ($cnt > 0 || ($this->date->dayOfWeek >= Carbon::MONDAY && $this->date->dayOfWeek <= Carbon::FRIDAY)) {
                Mail::to($this->email)
                    ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                    ->send(new JobStatus(
                        jobType: self::class,
                        status: 'Success',
                        file: 'API Import: '.$this->date->format('Y-m-d'),
                        subject: $this->subject,
                        rowCount: $cnt,
                        newUsers: $newUsers,
                        updatedUsers: $updatedUsers,
                    ));
            }

            if ($cnt > 0 && App::environment('production')) {
                GeneratePerformanceReportUploadJob::dispatch(
                    date: $this->date,
                );
            }

        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
