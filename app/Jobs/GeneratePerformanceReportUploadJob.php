<?php

namespace App\Jobs;

use App\Helpers\DateTimeHelper;
use App\Helpers\MicrosoftGraph;
use App\Helpers\WorksheetHelper;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerAgentPerformance;
use App\Models\DialerNotificationType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Graph;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class GeneratePerformanceReportUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:SHAREPOINT-PERFORMANCE-REPORT';

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
        protected Carbon $date,
    ) {
        $this->file = "{$this->date->format('n-j-Y')} hours.csv";
        $this->subject = 'PowerBI - Hours: '.$this->date->format('Y-m-d');

        $log = new AuditLog();
        $log->action = self::ACTION_NAME;
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'file' => $this->file,
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
            $headers = [
                'Agent Name',
                'Calls',
                'Contacts',
                'Talk %',
                'Wait %',
                'Pause %',
                'Wrap Up %',
                'Talk Time+',
                'Wait Time',
                'Pause Time',
                'Wrap Up Time',
                'Total Time',
                'Voicemail',
                'Others',
                'Voicemail %',
                'Others %',
            ];

            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $worksheet = new WorksheetHelper($spreadsheet);
            $spreadsheet->addSheet($worksheet, 0);

            $worksheet->fromArray(
                $headers,
            );

            $rows = DialerAgentPerformance::query()
                ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
                ->select([
                    'dialer_agents.agent_name',
                    DB::raw("IFNULL(dialer_agent_performances.calls,0) AS calls_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.contacts,0) AS contacts_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.talk_pct,0) AS talk_pct_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.wait_pct,0) AS wait_pct_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.pause_pct,0) AS pause_pct_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.wrapup_pct,0) AS wrapup_pct_raw"),
                    DB::raw('SEC_TO_TIME(IFNULL(dialer_agent_performances.talk_time,0)) AS talk_time_raw'),
                    DB::raw('SEC_TO_TIME(IFNULL(dialer_agent_performances.wait_time,0)) AS wait_time_raw'),
                    DB::raw('SEC_TO_TIME(IFNULL(dialer_agent_performances.pause_time,0)) AS pause_time_raw'),
                    DB::raw('SEC_TO_TIME(IFNULL(dialer_agent_performances.wrapup_time,0)) AS wrapup_time_raw'),
                    DB::raw('SEC_TO_TIME(IFNULL(dialer_agent_performances.total_time,0)) AS total_time_raw'),
                    DB::raw("IFNULL(dialer_agent_performances.voicemail,0) AS voicemail_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.others,0) AS others_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.voicemail_pct,0) AS voicemail_pct_raw"),
                    DB::raw("IFNULL(dialer_agent_performances.others_pct,0) AS others_pct_raw"),
                ])
                ->whereDate('file_date', $this->date)
                ->where('calls', '>', 0)
                ->get();

            $worksheet->fromArray(
                $rows->toArray(),
                null,
                'A2',
                true
            );

            $totals = [
                'Total',
                $rows->sum('calls_raw'),
                $rows->sum('contacts_raw'),
                round($rows->avg('talk_pct_raw'), 2),
                round($rows->avg('wait_pct_raw'), 2),
                round($rows->avg('pause_pct_raw'), 2),
                round($rows->avg('wrapup_pct_raw'), 2),
                DateTimeHelper::seconds2time($rows->sum(function ($item) {
                    return Carbon::parse($item->talk_time_raw)->secondsSinceMidnight();
                })),
                DateTimeHelper::seconds2time($rows->sum(function ($item) {
                    return Carbon::parse($item->wait_time_raw)->secondsSinceMidnight();
                })),
                DateTimeHelper::seconds2time($rows->sum(function ($item) {
                    return Carbon::parse($item->pause_time_raw)->secondsSinceMidnight();
                })),
                DateTimeHelper::seconds2time($rows->sum(function ($item) {
                    return Carbon::parse($item->wrapup_time_raw)->secondsSinceMidnight();
                })),
                DateTimeHelper::seconds2time($rows->sum(function ($item) {
                    return Carbon::parse($item->total_time_raw)->secondsSinceMidnight();
                })),
                $rows->sum('voicemail_raw'),
                $rows->sum('others_raw'),
                round($rows->avg('voicemail_pct_raw'), 2),
                round($rows->avg('others_pct_raw'), 2),
            ];

            $worksheet->fromArray(
                $totals,
                null,
                'A'.$worksheet->getHighestDataRow() + 1,
                true
            );

            Storage::makeDirectory('sharepoint');
            $filepath = Storage::disk('local')->path("sharepoint/{$this->file}");

            $writer = new Csv($spreadsheet);
            $writer->save($filepath);

            $access_token = MicrosoftGraph::getOrRefreshAccessToken('sharepoint');

            $graph = new Graph();
            $graph->setAccessToken($access_token->getToken());

            MicrosoftGraph::uploadLargeFile(
                graph: $graph,
                siteId: config('microsoft.sharepoint.site_id'),
                itemId: config('microsoft.sharepoint.performance_item_id'),
                inputPath: $filepath,
                destinationFile: $this->file
            );

            $this->markLogAsSuccess();

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    file: basename($this->file),
                    subject: $this->subject,
                ));

        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
