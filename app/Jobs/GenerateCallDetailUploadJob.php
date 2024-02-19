<?php

namespace App\Jobs;

use App\Helpers\MicrosoftGraph;
use App\Helpers\WorksheetHelper;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerLog;
use App\Models\DialerNotificationType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
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

class GenerateCallDetailUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:SHAREPOINT-CALL-DETAIL';

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
        protected $file = null,
    ) {
        $this->file = "{$this->date->format('n-j-Y')} dispo.csv";
        $this->subject = 'PowerBI - Dispos: '.$this->date->format('Y-m-d');

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
                'Call Log ID',
                'Lead ID',
                'First Name',
                'Last Name',
                'Email',
                'Number Dialed',
                'Caller ID',
                'Status Name',
                'Time Stamp (PST)',
                'Agent Name',
                'Talk Time',
                'Bill Time',
                'Queue Wait Time',
                'Cost',
                'Call Type',
                'Termination Reason',
                'Outbound Called Count',
                'Campaign Name',
                'List Name',
                'Queue Name',
                'Recordings',
                'GMT Offset Now',
                'Address 1',
                'City',
                'State',
                'Postal Code',
                'Comment',
                'Audio Quality',
                'Revenue',
                'Returned',
                'Agent Email',
                'Agent Extension',
                'Agent First Name',
                'Agent ID',
                'Agent Last Name',
                'Called Since Last Reset',
                'Wrap-up Time',
                'Date and Hour',
                'Day of Month',
                'Day of Week',
                'End Epoch',
                'Hour',
                'Month',
                'Start Epoch',
                'Time',
                'Year',
                'Alternate Number to Dial',
                'Campaign ID',
                'Campaign Type',
                'Queue ID',
                'Session ID',
                'Status',
                'Originating Agent ID',
                'Originating Agent Name',
                'Lead Owner',
                'Last Modified By',
                'Created By',
                'Beneficiary Last Name',
                'Beneficiary Relationship',
                'Carrier Name',
                'Carrier Type',
                'Cell Phone',
                'Country',
                'Country Code',
                'Current Life Ins?',
                'Currently Employed?',
                'Date Of Birth',
                'Email Consent?',
                'Final Reached At',
                'FL Call Counter',
                'Gender',
                'Have children?',
                'Height',
                'IB Call Campaign',
                'IB Call Source',
                'Illnesses',
                'Income',
                'Insurance Purpose',
                'Integriant Lead ID',
                'Jornaya Lead ID',
                'Last Called (PST)',
                'Last Reached At',
                'Last Viewed',
                'Last DNC Check Date',
                'Leadspedia Campaign Name',
                'Leadspedia Lead ID',
                'List',
                'Major Illnesses',
                'Marital Status',
                'Medicaid Low Income',
                'Medicare A and B?',
                'Nicotine Frequency',
                'Notes',
                'Original Lead Gen Date',
                'Payment Type',
                'Primary Phone',
                'Product',
                'Province',
                'Security Phrase',
                'Send Life Data To Retreaver',
                'Created At (Time)',
                'Last Modify',
                'Source Name',
                'Sub Product',
                'TCPA Date',
                'Tobacco Use',
                'Trackdrive Status',
                'Trusted Form Cert ID',
                'Vendor Code',
                'Vertical',
                'Weight (lbs)',
                'Work Phone',
                'Zoho Contact ID',
            ];

            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $worksheet = new WorksheetHelper($spreadsheet);
            $spreadsheet->addSheet($worksheet, 0);

            $worksheet->fromArray(
                $headers,
            );

            $rows = DialerLog::query()
                ->select([
                    'dialer_logs.call_id',
                    'dialer_logs.lead_id',
                    'dialer_logs.first_name',
                    'dialer_logs.last_name',
                    'dialer_logs.email',
                    'dialer_logs.number_dialed',
                    'dialer_logs.caller_id',
                    'dialer_logs.status_name',
                    'dialer_logs.time_stamp',
                    'dialer_logs.agent_name',
                    'dialer_logs.talk_time',
                    'dialer_logs.bill_time',
                    'dialer_logs.queue_wait_time',
                    'dialer_logs.cost',
                    'dialer_logs.call_type',
                    'dialer_logs.termination_reason',
                    'dialer_logs.outbound_called_count',
                    'dialer_logs.campaign_name',
                    'dialer_logs.list_name',
                    'dialer_logs.queue_name',
                    'dialer_logs.recordings',
                    'dialer_logs.gmt_offset_now',
                    'dialer_logs.address_1',
                    'dialer_logs.city',
                    'dialer_logs.state',
                    'dialer_logs.postal_code',
                    'dialer_logs.comment',
                    'dialer_logs.audio_quality',
                    'dialer_logs.revenue',
                    'dialer_logs.returned',
                    'dialer_logs.agent_email',
                    'dialer_logs.agent_extension',
                    'dialer_logs.agent_first_name',
                    'dialer_logs.agent_id',
                    'dialer_logs.agent_last_name',
                    'dialer_logs.called_since_last_reset',
                    'dialer_logs.wrapup_time',
                    'dialer_logs.date_and_hour',
                    'dialer_logs.day_of_month',
                    'dialer_logs.day_of_week',
                    'dialer_logs.end_epoch',
                    'dialer_logs.hour',
                    'dialer_logs.month',
                    'dialer_logs.start_epoch',
                    'dialer_logs.time',
                    'dialer_logs.year',
                    'dialer_logs.alternate_number_to_dial',
                    'dialer_logs.campaign_id',
                    'dialer_logs.campaign_type',
                    'dialer_logs.queue_id',
                    'dialer_logs.session_id',
                    'dialer_logs.status',
                    'dialer_logs.originating_agent_id',
                    'dialer_logs.originating_agent_name',
                    'dialer_logs.lead_owner',
                    'dialer_logs.last_modified_by',
                    'dialer_logs.created_by',
                    'dialer_logs.beneficiary_last_name',
                    'dialer_logs.beneficiary_relationship',
                    'dialer_logs.carrier_name',
                    'dialer_logs.carrier_type',
                    'dialer_logs.cell_phone',
                    'dialer_logs.country',
                    'dialer_logs.country_code',
                    'dialer_logs.current_life_ins',
                    'dialer_logs.currently_employed',
                    'dialer_logs.date_of_birth',
                    'dialer_logs.email_consent',
                    'dialer_logs.final_reached_at',
                    'dialer_logs.fl_call_counter',
                    'dialer_logs.gender',
                    'dialer_logs.have_children',
                    'dialer_logs.height',
                    'dialer_logs.ib_call_campaign',
                    'dialer_logs.ib_call_source',
                    'dialer_logs.illnesses',
                    'dialer_logs.insurance_purpose',
                    'dialer_logs.income',
                    'dialer_logs.integriant_lead_id',
                    'dialer_logs.jornaya_lead_id',
                    'dialer_logs.last_called',
                    'dialer_logs.last_reached_at',
                    'dialer_logs.last_viewed',
                    'dialer_logs.last_dnc_check_date',
                    'dialer_logs.leadspedia_campaign_name',
                    'dialer_logs.leadspedia_lead_id',
                    'dialer_logs.list',
                    'dialer_logs.major_illnesses',
                    'dialer_logs.marital_status',
                    'dialer_logs.medicaid_low_income',
                    'dialer_logs.medicare_a_and_b',
                    'dialer_logs.nicotine_frequency',
                    'dialer_logs.notes',
                    'dialer_logs.original_lead_gen_date',
                    'dialer_logs.payment_type',
                    'dialer_logs.primary_phone',
                    'dialer_logs.product',
                    'dialer_logs.province',
                    'dialer_logs.security_phrase',
                    'dialer_logs.send_life_data_to_retreaver',
                    'dialer_logs.created_at',
                    'dialer_logs.last_modify',
                    'dialer_logs.source_name',
                    'dialer_logs.sub_product',
                    'dialer_logs.tcpa_date',
                    'dialer_logs.tobacco_use',
                    'dialer_logs.trackdrive_status',
                    'dialer_logs.trusted_form_cert_id',
                    'dialer_logs.vendor_code',
                    'dialer_logs.vertical',
                    'dialer_logs.weight',
                    'dialer_logs.work_phone',
                    'dialer_logs.zoho_contact_id',
                ])
                ->timestampQuery($this->date->format('Y-m-d'))
                ->lazy();

            $rowCnt = 2;
            $rows->each(function ($row) use ($worksheet, &$rowCnt) {
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
                itemId: config('microsoft.sharepoint.dispo_item_id'),
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
