<?php

namespace App\Jobs;

use App\Helpers\MicrosoftGraph;
use App\Helpers\WorksheetHelper;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerNotificationType;
use App\Models\DialerRetreaverLog;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Graph;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class GenerateRetreaverUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:SHAREPOINT-RETREAVER-LOG';
    const DESCRIPTION = 'Upload Retreaver to Sharepoint';


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
        protected $file = null,
    ) {
        $this->file = "{$this->date->format('n-j-Y')} ret.csv";
        $this->subject = 'PowerBI - Retreaver: '.$this->date->format('Y-m-d');

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
            $headers = collect([
                'Timestamp' => 'time_stamp',
                'CID' => 'cid',
                'CampaignName' => 'campaign_name',
                'PubID' => 'pub_id',
                'PublisherName' => 'publisher_name',
                'SubID' => 'sub_id',
                'Number' => 'number',
                'NumberName' => 'number_name',
                'TotalDurationSecs' => 'total_duration_secs',
                'IVRDurationSecs' => 'ivr_duration_secs',
                'HoldDurationSecs' => 'hold_duration_secs',
                'ConnectedSecs' => 'connected_secs',
                'ConnectedTo' => 'connected_to',
                'BillableMinutes' => 'billable_minutes',
                'Charged' => 'charged',
                'Caller' => 'caller',
                'ReceivedCallerID' => 'received_caller_id',
                'SentCallerID' => 'sent_caller_id',
                'CallerCity' => 'caller_city',
                'CallerState' => 'caller_state',
                'CallerZip' => 'caller_zip',
                'CallerCountry' => 'caller_country',
                'CallUUID' => 'call_uuid',
                'RecordingURL' => 'recording_url',
                'FiredPixels' => 'fired_pixels',
                'PostbackValue' => 'postback_value',
                'Revenue' => 'revenue',
                'Payout' => 'payout',
                'TargetID' => 'target_id',
                'TargetName' => 'target_name',
                'Converted' => 'converted',
                'Duplicate' => 'duplicate',
                'Repeat' => 'repeat',
                'Status' => 'status',
                'HungUpBy' => 'hung_up_by',
                'Receivable' => 'receivable',
                'Payable' => 'payable',
                'SessionNotes' => 'session_notes',
                'VisitorURL' => 'visitor_url',
                'tag_list' => 'tag_list',
            ]);

            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $worksheet = new WorksheetHelper($spreadsheet);
            $spreadsheet->addSheet($worksheet, 0);

            $worksheet->fromArray(
                $headers->keys()->toArray(),
            );

            $rows = DialerRetreaverLog::query()
                ->select($headers->values()->toArray())
                ->timestampQuery($this->date->format('Y-m-d'))
                ->lazy();

            $rowCnt = 2;
            $rows->each(function ($row) use ($worksheet, &$rowCnt) {
                $row->converted = !empty($row->converted) ? 'yes' : 'no';
                $row->duplicate = !empty($row->duplicate) ? 'yes' : 'no';
                $row->repeat = !empty($row->repeat) ? 'yes' : 'no';
                $row->receivable = !empty($row->receivable) ? 'yes' : 'no';
                $row->payable = !empty($row->payable) ? 'yes' : 'no';

                $worksheet->fromArray(
                    $row->toArray(),
                    null,
                    'A'.$rowCnt++,
                    true
                );
            });

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
                itemId: config('microsoft.sharepoint.retreaver_item_id'),
                inputPath: $filepath,
                destinationFile: $this->file
            );

            $this->markLogAsSuccess();

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::DESCRIPTION,
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
