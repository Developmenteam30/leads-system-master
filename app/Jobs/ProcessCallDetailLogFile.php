<?php

namespace App\Jobs;

use App\Mail\JobStatus;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerLog;
use App\Models\DialerNotificationType;
use App\Models\DialerStatus;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessCallDetailLogFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 1;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    protected $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $date, $email, $logId = null)
    {
        $this->file = $file;
        $this->date = $date;
        $this->email = $email;
        $this->logId = $logId;
        $this->subject = 'Dialer Logs Upload: '.$this->date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

        Log::info("ProcessCallDetailLogFile: START");

        if (!file_exists($this->file)) {
            Log::error("File does not exist: {$this->file}");
            $this->fail(new \Exception("File does not exist: {$this->file}"));

            return;
        }

        $reader = IOFactory::createReaderForFile($this->file);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($this->file);
        $worksheet = $spreadsheet->getSheet(0);
        if (!$worksheet) {
            Log::error('Cannot open default worksheet.');
            $this->fail(new \Exception('Cannot open default worksheet.'));

            return;
        }

        Log::info("ProcessCallDetailLogFile: WORKSHEET LOADED");

        $newUsers = $updatedUsers = [];

        try {
            DB::transaction(function () use ($worksheet, &$newUsers, &$updatedUsers) {

                $firstSaneDate = new \DateTime('2000-01-01');
                $lastSaneDate = new \DateTime('2050-01-01');

                $columns = [
                    'Address 1' => 'address_1',
                    'Agent Email' => 'agent_email',
                    'Agent Extension' => 'agent_extension',
                    'Agent First Name' => 'agent_first_name',
                    'Agent ID' => 'agent_id',
                    'Agent Last Name' => 'agent_last_name',
                    'Agent Name' => 'agent_name',
                    'Alternate Number to Dial' => 'alternate_number_to_dial',
                    'Audio Quality' => 'audio_quality',
                    'Beneficiary Last Name' => 'beneficiary_last_name',  // Made optional per #6987
                    'Beneficiary Relationship' => 'beneficiary_relationship', // Made optional per #6987
                    'Bill Time' => 'bill_time',
                    'Call Log ID' => 'call_id',
                    'Call Type' => 'call_type',
                    'Called Since Last Reset' => 'called_since_last_reset',
                    'Caller ID' => 'caller_id',
                    'Campaign ID' => 'campaign_id',
                    'Campaign Name' => 'campaign_name',
                    'Campaign Type' => 'campaign_type',
                    'Carrier Name' => 'carrier_name',
                    'Carrier Type' => 'carrier_type',
                    'Cell Phone' => 'cell_phone',
                    'City' => 'city',
                    'Comment' => 'comment',
                    'Cost' => 'cost',
                    'Country Code' => 'country',
                    'Country' => 'country_code',
                    'Created At (Time)' => 'created_at',
                    'Created By' => 'created_by',
                    'Current Life Ins?' => 'current_life_ins',
                    'Currently Employed?' => 'currently_employed',
                    'Date and Hour' => 'date_and_hour',
                    'Date Of Birth' => 'date_of_birth',
                    'Day of Month' => 'day_of_month',
                    'Day of Week' => 'day_of_week',
                    'Email Consent?' => 'email_consent',
                    'Email' => 'email',
                    'End Epoch' => 'end_epoch',
                    'Final Reached At' => 'final_reached_at',
                    'First Name' => 'first_name',
                    'FL Call Counter' => 'fl_call_counter',
                    'Gender' => 'gender',
                    'GMT Offset Now' => 'gmt_offset_now',
                    'Have children?' => 'have_children',
                    'Height' => 'height',
                    'Hour' => 'hour',
                    'IB Call Campaign' => 'ib_call_campaign',
                    'IB Call Source' => 'ib_call_source',
                    'Illnesses' => 'illnesses',
                    'Income' => 'income',
                    'Insurance Purpose' => 'insurance_purpose',
                    'Integriant Lead ID' => 'integriant_lead_id',
                    'Jornaya Lead ID' => 'jornaya_lead_id',
                    'Last Called (PST)' => 'last_called',
                    'Last DNC Check Date' => 'last_dnc_check_date',
                    'Last Modified By' => 'last_modified_by',
                    'Last Modify' => 'last_modify',
                    'Last Name' => 'last_name',
                    'Last Reached At' => 'last_reached_at',
                    'Last Viewed' => 'last_viewed',
                    'Lead ID' => 'lead_id',
                    'Lead Owner' => 'lead_owner',
                    'Leadspedia Campaign Name' => 'leadspedia_campaign_name',
                    'Leadspedia Lead ID' => 'leadspedia_lead_id',
                    'List Name' => 'list_name',
                    'List' => 'list',
                    'Major Illnesses' => 'major_illnesses',
                    'Marital Status' => 'marital_status',
                    'Medicaid Low Income' => 'medicaid_low_income',
                    'Medicare A and B?' => 'medicare_a_and_b',
                    'Mini-TCPA Block Expires' => 'mini_tcpa_block_expires', // Added per #6987 and made optional per #6998
                    'Mini-TCPA Blocked' => 'mini_tcpa_blocked', // Added per #6987 and made optional per #6998
                    'Month' => 'month',
                    'Nicotine Frequency' => 'nicotine_frequency',
                    'Notes' => 'notes',
                    'Number Dialed' => 'number_dialed',
                    'Original Lead Gen Date' => 'original_lead_gen_date',
                    'Originating Agent ID' => 'originating_agent_id',
                    'Originating Agent Name' => 'originating_agent_name',
                    'Outbound Called Count' => 'outbound_called_count',
                    'Payment Type' => 'payment_type',
                    'Postal Code' => 'postal_code',
                    'Primary Phone' => 'primary_phone',
                    'Product' => 'product',
                    'Province' => 'province',
                    'Queue ID' => 'queue_id',
                    'Queue Name' => 'queue_name',
                    'Queue Wait Time' => 'queue_wait_time',
                    'Recordings' => 'recordings',
                    'Returned' => 'returned',
                    'Revenue' => 'revenue',
                    'Security Phrase' => 'security_phrase',
                    'Send Life Data To Retreaver' => 'send_life_data_to_retreaver',
                    'Send Medicare Data To Retreaver' => 'send_medicare_data_to_retreaver',
                    'Session ID' => 'session_id',
                    'Source Name' => 'source_name',
                    'Start Epoch' => 'start_epoch',
                    'State' => 'state',
                    'Status Name' => 'status_name',
                    'Status' => 'status',
                    'Sub Product' => 'sub_product',
                    'Talk Time' => 'talk_time',
                    'TCPA Date' => 'tcpa_date',
                    'Termination Reason' => 'termination_reason',
                    'Time Stamp (PST)' => 'time_stamp',
                    'Time' => 'time',
                    'Tobacco Use' => 'tobacco_use',
                    'Trackdrive Status' => 'trackdrive_status',
                    'Trusted Form Cert ID' => 'trusted_form_cert_id',
                    'Vendor Code' => 'vendor_code',
                    'Vertical' => 'vertical',
                    'Weight (lbs)' => 'weight',
                    'Work Phone' => 'work_phone',
                    'Wrap-up Time' => 'wrapup_time',
                    'Year' => 'year',
                    'Zoho Contact ID' => 'zoho_contact_id',
                ];

                $optionalColumns = [
                    'Beneficiary Last Name',  // Made optional per #6987
                    'Beneficiary Relationship', // Made optional per #6987
                    'Email Consent?', // Made optional per #7040
                    'Mini-TCPA Block Expires', // Made optional per #6998
                    'Mini-TCPA Blocked', // Made optional per #6998
                    'Send Life Data To Retreaver', // Made optional per #7060
                    'Send Medicare Data To Retreaver',
                    'Security Phrase', // Made optional per #7858
                    'Province', // Made optional per #7858
                ];

                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                $columnMap = [];

                // Ensure all columns that we expect are present are record their order
                foreach ($columns as $columnKey => $columnValue) {
                    $found = false;
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        $value = trim($worksheet->getCell([$col, 1])->getValue());
                        if ($value === $columnKey) {
                            $columnMap[$col] = $columnValue;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found && !in_array($columnKey, $optionalColumns)) {
                        throw new \Exception("Cannot find column: {$columnKey}");
                    }
                }

                Log::info("ProcessCallDetailLogFile: COLUMNS CHECKED");

                $checkedAgents = [];
                $checkedStatuses = [];

                // Skip the header row
                for ($row = 2; $row <= $highestRow; ++$row) {

                    if ($row % 1000 === 0) {
                        Log::info("ProcessCallDetailLogFile: {$row}");
                    }

                    $values = [];

                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        $value = $worksheet->getCell([$col, $row])->getValue();
                        if (isset($columnMap[$col])) {
                            $columnName = $columnMap[$col];
                            if (empty(trim($value))) {
                                $values[$columnName] = null;
                            } elseif (in_array($columnName, [
                                'created_at',
                                'date_and_hour',
                                'final_reached_at',
                                'last_called',
                                'last_dnc_check_date',
                                'last_modify',
                                'last_reached_at',
                                'last_viewed',
                                'mini_tcpa_block_expires',
                                'original_lead_gen_date',
                                'tcpa_date',
                                'time_stamp',
                            ])) {
                                try {
                                    $values[$columnName] = new \DateTime($value);
                                    if ($values[$columnName] < $firstSaneDate || $values[$columnName] > $lastSaneDate) {
                                        throw new \Exception("Encountered a bad {$columnName} value {$value} ({$values[$columnName]->format('c')} on row {$row}");
                                    }
                                } catch (\Exception $e) {
                                    // Do nothing
                                }
                            } elseif (in_array($columnName, [
                                'date_of_birth',
                            ])) {
                                try {
                                    $values[$columnName] = new \DateTime($value);
                                } catch (\Exception $e) {
                                    // Do nothing
                                }
                            } elseif (in_array($columnName, [
                                'end_epoch',
                                'start_epoch',
                            ])) {
                                $values[$columnName] = new \DateTime("@{$value}");
                            } elseif (in_array($columnName, [
                                'zoho_contact_id',
                            ])) {
                                $values[$columnName] = sprintf('%d', intval($value));
                            } else {
                                $values[$columnName] = Str::ascii($value);
                            }
                        }
                    }

                    // 6984: Exclude non-integer agents from being imported.
                    if (!preg_match('/^[0-9]/', $values['agent_name']) && !str_starts_with($values['agent_name'], 'System')) {
                        //Log::debug("Excluding {$values['agent_name']}");
                        continue;
                    }

                    if ($values['time_stamp']->format('Y-m-d') !== $this->date) {
                        throw new \Exception("Call date {$values['time_stamp']->format('Y-m-d')} in row #{$row} does not match upload date {$this->date}");
                    }

                    if (empty($values['status'])) {
                        var_dump($values);
                        throw new \Exception("Status is empty on row #{$row}");
                    }

                    if (!in_array($values['agent_id'], $checkedAgents)) {
                        $agent = DialerAgent::firstOrCreate(
                            [
                                'id' => $values['agent_id'],
                            ],
                            [
                                'agent_name' => $values['agent_name'],
                            ]
                        );
                        if ($agent->wasRecentlyCreated) {
                            DialerAgentEffectiveDate::createDefaultEntry($agent, $values['time_stamp']);
                            $newUsers[] = "{$values['agent_id']} - {$values['agent_name']}";
                        }
                        $agent->save();

                        $effectiveDateRow = $agent->getEffectiveValuesForDate($this->date);
                        if (empty($effectiveDateRow)) {
                            DialerAgentEffectiveDate::createDefaultEntry($agent, $this->date);
                            $updatedUsers[] = "{$values['agent_id']} - {$values['agent_name']}";
                        }

                        $checkedAgents[] = $values['agent_id'];
                    }

                    if (!in_array($values['status'], $checkedStatuses)) {
                        DialerStatus::updateOrCreate(
                            [
                                'status' => $values['status'],
                            ],
                            [
                                'status_name' => $values['status_name'],
                            ]
                        );
                        $checkedStatuses[] = $values['status'];
                    }

                    DialerLog::updateOrCreate(
                        [
                            'call_id' => $values['call_id'],
                        ],
                        $values
                    );
                }

                Log::info("ProcessCallDetailLogFile: END OF ROW LOOP");


                $this->markLogAsSuccess();
            }, 5);

            Log::info("ProcessCallDetailLogFile: FINISHED");

            SummarizeCallDetailLog::dispatch($this->date, $this->email);
            if (App::environment('production')) {
                GenerateCallDetailUploadJob::dispatch(
                    date: Carbon::parse($this->date),
                );

//                UploadOnScriptRecordingsJob::dispatch(
//                    date: CarbonImmutable::parse($this->date),
//                );
            }

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    file: basename($this->file),
                    subject: $this->subject,
                    rowCount: $worksheet->getHighestRow(),
                    newUsers: $newUsers,
                    updatedUsers: $updatedUsers,
                ));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
