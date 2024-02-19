<?php

namespace App\Jobs;

use App\External\Convoso\APIRequest;
use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use App\Models\DialerExternalCampaign;
use App\Models\DialerLog;
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

class ProcessCallLogAPIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:CALL-LOG-API';

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected CarbonImmutable $date,
        protected $logId = null,
        protected $sendEmail = false,
    ) {
        $this->subject = 'Call Log API Import: '.$this->date->format('n/j/Y');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

        try {
            DB::beginTransaction();

            $convoso = new APIRequest();
            $limit = 1000;

            $required_fields = [
                'id',
                'call_date',
                'user_id',
            ];

            $agents = DialerAgent::query()
                ->isActiveForDate($this->date)
                ->where('dialer_agents.agent_name', 'NOT LIKE', 'System%')
                ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
                ->select([
                    'dialer_agents.id',
                ])
                ->get();

            $campaigns = DialerExternalCampaign::query()
                ->get()
                ->pluck('id');

            foreach ($agents as $agent) {
                $offset = 0;

                do {
                    $rows = $convoso->getCallLogs([
                        'start_time' => $this->date->setTime(0, 0)->toDateTimeLocalString(),
                        'end_time' => $this->date->setTime(23, 59, 59)->toDateTimeLocalString(),
                        'user_id' => $agent->id,
                        'order' => 'ASC',
                        'limit' => $limit,
                        'offset' => $offset,
                    ]);

                    foreach ($rows->results as $row) {

                        foreach ($required_fields as $required_field) {
                            if (empty($row->{$required_field})) {
                                throw new \Exception("Required field is missing: {$required_field}");
                            }
                        }

                        // Skip campaigns we're not watching
                        if ($campaigns->doesntContain($row->campaign_id)) {
                            continue;
                        }

                        DialerLog::updateOrCreate([
                            'call_id' => $row->id,
                        ], [
                            'lead_id' => $row->lead_id ?? null,
                            'list' => $row->list_id ?? null,
                            'campaign_id' => $row->campaign_id ?? null,
                            'campaign_name' => $row->campaign ?? null,
                            'queue_name' => $row->queue ?? null,
                            'agent_name' => $row->user ?? null,
                            'agent_id' => $row->user_id ?? null,
                            'number_dialed' => $row->phone_number ?? null,
                            'first_name' => $row->first_name ?? null,
                            'last_name' => $row->last_name ?? null,
                            'status' => $row->status ?? null,
                            'status_name' => $row->status_name ?? null,
                            'talk_time' => $row->call_length ?? null,
                            'time_stamp' => $row->call_date ?? null,
                            'notes' => $row->agent_comment ?? null,
                            'queue_id' => $row->queue_id ?? null,
                            'outbound_called_count' => $row->called_count ?? null,
                            'caller_id' => $row->caller_id_displayed ?? null,
                            'termination_reason' => $row->term_reason ?? null,
                            'call_type' => $row->call_type ?? null,
                            'queue_wait_time' => $row->queue_seconds ?? null,
                            'originating_agent_id' => $row->originating_agent_id ?? null,
                        ]);

                    }

                    $offset += $limit;

                } while ($rows->entries >= $limit);

            }

            DB::commit();

            if ($this->sendEmail) {
                GenerateAttendanceDetailEmail::dispatch(
                    date: $this->date,
                );
            }

            $this->markLogAsSuccess();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
