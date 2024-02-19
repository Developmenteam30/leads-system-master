<?php

namespace App\Jobs;

use App\Mail\JobStatus;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerNotificationType;
use App\Services\CalculateEmployeeHoursService;
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
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessAgentPerformanceFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

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
        $this->subject = 'Agent Hours Upload: '.$this->date;
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

        $columns = [
            'Agent Name' => 'agent_name',
            'Calls' => 'calls',
            'Contacts' => 'contacts',
            'Talk %' => 'talk_pct',
            'Wait %' => 'wait_pct',
            'Pause %' => 'pause_pct',
            'Wrap Up %' => 'wrapup_pct',
            'Talk Time+' => 'talk_time',
            'Wait Time' => 'wait_time',
            'Pause Time' => 'pause_time',
            'Wrap Up Time' => 'wrapup_time',
            'Total Time' => 'total_time',
            'Training' => 'training',
        ];

        $optionalColumns = [
            'Training',
        ];

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $columnMap = [];

        // Ensure all columns that we expect are present are record their order
        foreach ($columns as $columnKey => $columnValue) {
            $found = false;
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $value = trim($worksheet->getCellByColumnAndRow($col, 1)->getValue());
                if ($value === $columnKey) {
                    $columnMap[$col] = $columnValue;
                    $found = true;
                    break;
                }
            }
            if (!$found && !in_array($columnKey, $optionalColumns)) {
                $this->fail(new \Exception("Cannot find column: {$columnKey}"));

                return;
            }
        }

        try {
            DB::beginTransaction();

            // Clear out existing records
            $rows = DialerAgentPerformance::query()
                ->whereDate('dialer_agent_performances.file_date', $this->date)
                ->get();

            $rows->each(function ($row) {
                DialerAgentPerformance::updateOrCreate([
                    'agent_id' => $row->agent_id,
                    'file_date' => $this->date,
                ], [
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
            });

            // Skip the header row
            for ($row = 2; $row <= $highestRow; ++$row) {
                $values = [];
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                    $value = trim($worksheet->getCellByColumnAndRow($col, $row)->getValue());

                    // Skip the total row
                    if (1 === $col && 'Total' === $value) {
                        continue 2;
                    }

                    if (isset($columnMap[$col])) {
                        $columnName = $columnMap[$col];
                        if (empty(trim($value))) {
                            $values[$columnName] = null;
                        } elseif (in_array($columnName, [
                            'talk_time',
                            'wait_time',
                            'pause_time',
                            'wrapup_time',
                            'total_time',
                        ])) {
                            if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $value, $matches)) {
                                $values[$columnName] = (intval($matches[1]) * 3600) + (intval($matches[2]) * 60) + intval($matches[3]);
                            } else {
                                $values[$columnName] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
                            }
                        } else {
                            $values[$columnName] = Str::ascii($value);
                        }
                    }
                }

                $values['net_time'] = ($values['talk_time'] ?? 0) + ($values['wait_time'] ?? 0) + ($values['wrapup_time'] ?? 0);
                $values['billable_time'] = floor($values['net_time'] / 60); // We only care about full minutes
                if ($values['billable_time'] > 240) {
                    $values['billable_time'] += 30;
                } else {
                    $values['billable_time'] += 15;
                }

                $values['billable_time_override'] = $values['billable_time'];

                $agent = DialerAgent::query()
                    ->select('dialer_agents.*')
                    ->where('agent_name', $values['agent_name'])
                    ->isActiveForDate($this->date)
                    ->first();

                if (!$agent) {
                    DB::rollback();

                    Log::error("Agent does not exist: {$values['agent_name']}");
                    $this->fail(new \Exception("Cannot find agent: {$values['agent_name']}"));

                    return;
                }

                // Unset helper fields that are not in the database
                unset($values['agent_name']);
                // 6356: Updated training rules; new logic in calculatePayments
                unset($values['training']);

                $record = DialerAgentPerformance::updateOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'file_date' => $this->date,
                    ],
                    $values
                );
                $record->save();

            }

            CalculateEmployeeHoursService::handle(Carbon::parse($this->date));

            $this->markLogAsSuccess();

            DB::commit();

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    file: basename($this->file),
                    subject: $this->subject,
                    rowCount: $highestRow,
                ));
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
