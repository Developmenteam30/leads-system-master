<?php

namespace App\Jobs;

use App\Mail\JobStatus;
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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessRetreaverFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    const ACTION_NAME = 'UPLOAD:RETREAVER-LOG';

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
        protected $file,
        protected CarbonImmutable $date,
        protected $logId = null,
        protected $email = null,
    ) {
        $this->subject = 'Retreaver Import: '.$this->date->format('Y-m-d');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

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

        try {
            DB::transaction(function () use ($worksheet, &$newUsers, &$updatedUsers) {

                $firstSaneDate = Carbon::parse('2000-01-01 00:00:00');
                $lastSaneDate = Carbon::parse('2050-01-01 23:59:59');

                $columns = [
                    'BillableMinutes' => 'billable_minutes',
                    'CID' => 'cid',
                    'CallUUID' => 'call_uuid',
                    'Caller' => 'caller',
                    'CallerCity' => 'caller_city',
                    'CallerCountry' => 'caller_country',
                    'CallerState' => 'caller_state',
                    'CallerZip' => 'caller_zip',
                    'CampaignName' => 'campaign_name',
                    'Charged' => 'charged',
                    'ConnectedSecs' => 'connected_secs',
                    'ConnectedTo' => 'connected_to',
                    'Converted' => 'converted',
                    'Duplicate' => 'duplicate',
                    'FiredPixels' => 'fired_pixels',
                    'HoldDurationSecs' => 'hold_duration_secs',
                    'HungUpBy' => 'hung_up_by',
                    'IVRDurationSecs' => 'ivr_duration_secs',
                    'Number' => 'number',
                    'NumberName' => 'number_name',
                    'Payable' => 'payable',
                    'Payout' => 'payout',
                    'PostbackValue' => 'postback_value',
                    'PubID' => 'pub_id',
                    'PublisherName' => 'publisher_name',
                    'Receivable' => 'receivable',
                    'ReceivedCallerID' => 'received_caller_id',
                    'RecordingURL' => 'recording_url',
                    'Repeat' => 'repeat',
                    'Revenue' => 'revenue',
                    'SentCallerID' => 'sent_caller_id',
                    'SessionNotes' => 'session_notes',
                    'Status' => 'status',
                    'SubID' => 'sub_id',
                    'TargetID' => 'target_id',
                    'TargetName' => 'target_name',
                    'Timestamp' => 'time_stamp',
                    'TotalDurationSecs' => 'total_duration_secs',
                    'VisitorURL' => 'visitor_url',
                    'tag_list' => 'tag_list',
                ];

                $optionalColumns = [
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

                // Skip the header row
                for ($row = 2; $row <= $highestRow; ++$row) {
                    $values = [];
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        $value = trim($worksheet->getCell([$col, $row])->getValue());

                        if (isset($columnMap[$col])) {
                            $columnName = $columnMap[$col];
                            if (empty(trim($value))) {
                                $values[$columnName] = null;
                            } elseif ($columnName == 'time_stamp') {
                                try {
                                    $values[$columnName] = Carbon::parse($value);
                                    if ($values[$columnName]->lt($firstSaneDate) || $values[$columnName]->gt($lastSaneDate)) {
                                        throw new \Exception("Encountered a bad {$columnName} value {$value} ({$values[$columnName]->format('c')} on row {$row}");
                                    }
                                } catch (\Exception $e) {
                                    // Do nothing
                                }
                            } else {
                                $values[$columnName] = Str::ascii($value);
                            }
                        }
                    }

                    if (empty($values['call_uuid'])) {
                        throw new \Exception("Call UUID field is empty on row {$row}");
                    }

                    if (!$values['time_stamp']->isSameDay($this->date)) {
                        throw new \Exception("Call date {$values['time_stamp']->format('Y-m-d')} in row #{$row} does not match upload date {$this->date}");
                    }

                    DialerRetreaverLog::updateOrCreate(
                        [
                            'call_uuid' => $values['call_uuid'],
                        ],
                        $values
                    );
                }

                $this->markLogAsSuccess();

            }, 5);

            if (App::environment('production')) {
                GenerateRetreaverUploadJob::dispatch(
                    date: $this->date,
                );
            }

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    file: basename($this->file),
                    subject: $this->subject,
                    rowCount: $worksheet->getHighestRow(),
                ));
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
