<?php

namespace App\Jobs;

use App\External\OnScript\APIRequest;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerLog;
use App\Models\DialerNotificationType;
use App\Models\DialerOnscriptRecordingUploadLog;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UploadOnScriptRecordingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:ONSCRIPT-RECORDINGS';

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
    ) {
        $this->subject = 'OnScript - Recordings: '.$this->date->format('n/j/Y');

        $log = new AuditLog();
        $log->action = self::ACTION_NAME;
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
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

            $successCount = $failCount = 0;
            $api = new APIRequest();

            /*
             * 7664: Processing rules
             *
             * Day 1 (Fri): successful xfers and xfer failed, 500 calls, shortest call to longest (cap 3 calls/agent)
             * Day 2 (Mon): DNC, 500 calls, shortest to longest (cap 3 calls/agent)
             * Day 3 (Tue): successful xfers and xfer failed, 500 calls, shortest call to longest (cap 3 calls/agent)
             * Day 4 (Wed): not interested, 500 calls, shortest to longest (cap 3 calls/agent)
             * Day 5 (Thu): successful xfers and xfer failed, 500 calls, shortest call to longest (cap 3 calls/agent)
             *
             */

            switch ($this->date->format('w')) {
                case 5: // Fri
                case 2: // Tue
                case 4: // Thu
                    $statuses = ['ST', 'STSSDI', 'TFCB'];
                    break;

                case 1: // Mon
                    $statuses = ['DNC', 'DNCA'];
                    break;

                case 3: // Wed
                    $statuses = ['NI'];
                    break;

                default:
                    throw new \Exception("Day of week not configured for upload to OnScript: {$this->date->format('l')}");

            }

            $sub = DialerLog::query()
                ->join('dialer_agents', 'dialer_agents.id', 'dialer_logs.agent_id')
                ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
                ->leftjoin('dialer_onscript_recording_upload_logs', 'dialer_logs.call_id', 'dialer_onscript_recording_upload_logs.call_id')
                ->select([
                    'dialer_logs.call_id',
                    'dialer_logs.recordings',
                    'dialer_logs.number_dialed',
                    'dialer_logs.caller_id',
                    'dialer_logs.status_name',
                    'dialer_logs.agent_name',
                    'dialer_logs.agent_id',
                    DB::raw('dialer_teams.name AS team_name'),
                    DB::raw('dialer_teams.id AS team_id'),
                    'dialer_logs.first_name',
                    'dialer_logs.last_name',
                    'dialer_logs.campaign_name',
                    'dialer_logs.bill_time',
                    'dialer_onscript_recording_upload_logs.onscript_id',
                    DB::raw('ROW_NUMBER() OVER(PARTITION BY agent_id ORDER BY bill_time ASC) as n'),
                ])
                ->whereNotNull('dialer_logs.recordings')
                ->timestampQuery($this->date->format('Y-m-d'))
                ->whereIn('dialer_logs.status', $statuses);

            $rows = DB::query()->fromSub($sub, 'x')
                ->where('n', '<=', 3)
                ->orderBy('bill_time')
                ->limit(500)
                ->lazy();

            $rows->each(function ($row) use ($api, &$successCount, &$failCount) {

                // Exclude previous successfully uploaded logs
                if (!empty($row->onscript_id)) {
                    return true;
                }

                /*
                 * 7664: Campaign rules
                 *
                 * For ST, STSSDI, and TFCB, continue to split between the Medicare and FE campaigns.
                 * For DNC and NI, only send Medicare calls.
                 *
                 */

                switch ($this->date->format('w')) {
                    case 5: // Fri
                    case 2: // Tue
                    case 4: // Thu
                        if (str_starts_with($row->campaign_name, 'SDR Life -')) {
                            $api_key = config('onscript.api_key_final_expense');
                        } elseif (str_starts_with($row->campaign_name, 'SDR Medicare -')) {
                            $api_key = config('onscript.api_key_medicare');
                        } else {
                            throw new \Exception("Unknown campaign: {$row->campaign_name}");
                        }
                        break;

                    case 1: // Mon
                        if (str_starts_with($row->campaign_name, 'SDR Medicare -')) {
                            $api_key = config('onscript.api_key_dnc');
                        } else {
                            return true;
                        }
                        break;

                    case 3: // Wed
                        if (str_starts_with($row->campaign_name, 'SDR Medicare -')) {
                            $api_key = config('onscript.api_key_ni');
                        } else {
                            return true;
                        }
                        break;

                    default:
                        throw new \Exception("Day of week not configured for upload to OnScript: {$this->date->format('l')}");

                }

                $log = new DialerOnscriptRecordingUploadLog();
                $log->log_id = $this->logId;
                $log->call_id = $row->call_id;

                try {
                    $response = $api->submitRecording([
                        'api_key' => $api_key,
                        'url' => $row->recordings,
                        'client_phone' => $row->number_dialed,
                        'ani_outbound' => $row->caller_id,
                        'call_disposition' => $row->status_name,
                        'agent_name' => $row->agent_name,
                        'agent_id' => $row->agent_id,
                        'team_name' => $row->team_name,
                        'team_id' => $row->team_id,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                    ]);

                    $log->onscript_id = $response->id;
                    $log->status = $response->status;
                    $successCount++;
                } catch (\Exception $e) {
                    $log->onscript_id = null;
                    $log->status = $e->getMessage();
                    $failCount++;
                }

                $log->save();
            });

            $this->markLogAsSuccess();

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    subject: $this->subject,
                    successCount: $successCount,
                    failCount: $failCount,
                ));

        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
