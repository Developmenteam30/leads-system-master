<?php

namespace App\Jobs;

use App\Datasets\PayrollReportDataset;
use App\Helpers\ExcelHelper;
use App\Helpers\WorksheetHelper;
use App\Mail\JobStatus;
use App\Mail\PayrollReport;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerNotificationType;
use App\Models\DialerProduct;
use App\Traits\JobStatusUpdateAuditLogTrait;
use DateInterval;
use DatePeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GeneratePayrollReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, JobStatusUpdateAuditLogTrait;

    protected array $filters;
    protected ?DialerAgent $user;

    /**
     * Create a new job instance.
     *
     * @param  array  $filters
     * @param  DialerAgent|null  $user
     * @param  string|null  $logId
     */
    public function __construct(array $filters, DialerAgent $user = null, string $logId = null)
    {
        $this->filters = $filters;
        $this->user = $user;
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);
            Cell::setValueBinder(new AdvancedValueBinder());

            $worksheet = new WorksheetHelper($spreadsheet, 'Payroll');
            $spreadsheet->addSheet($worksheet, 0);

            $agents = PayrollReportDataset::getWeeklyValues($this->filters);
            $hasHolidayHours = $agents->sum('holiday_amount') > 0;

            $rowCnt = 1;
            $colCnt = 1;
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Agent Name');
            $dates = new Collection(new DatePeriod($this->filters['start_date'], new DateInterval('P1D'), $this->filters['end_date']));
            $dates->each(function ($date) use ($worksheet, &$colCnt, $rowCnt) {
                $worksheet->setCellValueExplicit([$colCnt++, $rowCnt], $date->format('D n/j'), DataType::TYPE_STRING);
            });
            $colCnt++;
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Training Hours');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Training Rate (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Training Amount (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Regular Hours');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Regular Rate (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Regular Amount (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'QA Hours');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'QA Rate (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'QA Amount (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Overtime Hours');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Overtime Rate (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Overtime Amount (US$)');
            if ($hasHolidayHours) {
                $worksheet->setCellValue([$colCnt++, $rowCnt], 'Holiday Hours');
                $worksheet->setCellValue([$colCnt++, $rowCnt], 'Holiday Rate (US$)');
                $worksheet->setCellValue([$colCnt++, $rowCnt], 'Holiday Amount (US$)');
            }
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Payroll Amount (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Bonus Amount (US$)');
            $worksheet->setCellValue([$colCnt++, $rowCnt], 'Total Amount (US$)');

            $agents->each(function ($agent) use ($worksheet, $dates, &$rowCnt, $hasHolidayHours) {
                $rowCnt++;
                $colCnt = 1;
                $worksheet->setCellValue([$colCnt++, $rowCnt], $agent['agent_name']);

                $dates->each(function ($date) use ($worksheet, $agent, &$colCnt, &$rowCnt) {
                    $dateStr = strtolower($date->format('D'));
                    $worksheet->setCellValue([$colCnt++, $rowCnt], $agent[$dateStr] ?? '');
                });

                $lastRegularRate = DialerAgentPerformance::query()
                    ->whereDate('dialer_agent_performances.file_date', '<', $this->filters['start_date'])
                    ->where('dialer_agent_performances.agent_id', $agent['agent_id'])
                    ->where('payable_training', '0')
                    ->select([
                        'payable_rate',
                    ])
                    ->orderBy('file_date', 'DESC')
                    ->pluck('payable_rate')
                    ->first();

                $lastTrainingRate = DialerAgentPerformance::query()
                    ->whereDate('dialer_agent_performances.file_date', '<', $this->filters['start_date'])
                    ->where('dialer_agent_performances.agent_id', $agent['agent_id'])
                    ->where('payable_training', '1')
                    ->select([
                        'payable_rate',
                    ])
                    ->orderBy('file_date', 'DESC')
                    ->pluck('payable_rate')
                    ->first();

                $colCnt++;
                $worksheet->setCellValue([$colCnt++, $rowCnt], !empty($agent['training_hours']) && $agent['training_hours'] > 0 ? $agent['training_hours'] : '');
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['training_hours']) && $agent['training_hours'] > 0 ? $agent['training_rate'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                if (!empty($agent['training_hours']) && $agent['training_hours'] > 0 && !empty($lastTrainingRate) && $lastTrainingRate > 0 && $lastTrainingRate != $agent['training_rate']) {
                    $worksheet->getCell([$colCnt - 1, $rowCnt])
                        ->getStyle()
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAC800');
                }
                $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'1');
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['training_amount']) && $agent['training_amount'] > 0 ? $agent['training_amount'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);

                $worksheet->setCellValue([$colCnt++, $rowCnt], !empty($agent['regular_hours']) && $agent['regular_hours'] > 0 ? $agent['regular_hours'] : '');
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['regular_hours']) && $agent['regular_hours'] > 0 ? $agent['regular_rate'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                if (!empty($agent['regular_hours']) && $agent['regular_hours'] > 0 && !empty($lastRegularRate) && $lastRegularRate > 0 && $lastRegularRate != $agent['regular_rate']) {
                    $worksheet->getCell([$colCnt - 1, $rowCnt])
                        ->getStyle()
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAC800');
                }
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['regular_amount']) && $agent['regular_amount'] > 0 ? $agent['regular_amount'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);

                $worksheet->setCellValue([$colCnt++, $rowCnt], !empty($agent['qa_hours']) && $agent['qa_hours'] > 0 ? $agent['qa_hours'] : '');
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['qa_hours']) && $agent['qa_hours'] > 0 ? $agent['qa_rate'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                if (!empty($agent['qa_hours']) && $agent['qa_hours'] > 0 && !empty($lastRegularRate) && $lastRegularRate > 0 && $lastRegularRate != $agent['qa_rate']) {
                    $worksheet->getCell([$colCnt - 1, $rowCnt])
                        ->getStyle()
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFAC800');
                }
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['qa_amount']) && $agent['qa_amount'] > 0 ? $agent['qa_amount'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);

                $worksheet->setCellValue([$colCnt++, $rowCnt], !empty($agent['overtime_hours']) && $agent['overtime_hours'] > 0 ? $agent['overtime_hours'] : '');
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['overtime_hours']) && $agent['overtime_hours'] > 0 ? $agent['overtime_rate'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['overtime_amount']) && $agent['overtime_amount'] > 0 ? $agent['overtime_amount'] : '',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);

                if ($hasHolidayHours) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt], !empty($agent['holiday_hours']) && $agent['holiday_hours'] > 0 ? $agent['holiday_hours'] : '');
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['holiday_hours']) && $agent['holiday_hours'] > 0 ? $agent['holiday_rate'] : '',
                        ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($agent['holiday_amount']) && $agent['holiday_amount'] > 0 ? $agent['holiday_amount'] : '',
                        ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                }

                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $agent['payroll_amount'],
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $agent['bonus_amount'],
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $agent['total_amount'],
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE);
            });

            if ($rowCnt > 1) {
                $rowCnt++;
                $colCnt = 1;
                $worksheet->setCellValue([$colCnt++, $rowCnt], 'TOTAL'); // A

                $dates->each(function ($date) use ($worksheet, &$colCnt, &$rowCnt) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt],
                        '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')'); // B-F
                });
                $colCnt++; // I
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_NUMBER_COMMA_SEPARATED2); // J
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=IFERROR(AVERAGE('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).'),0.00)',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // K
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // L
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_NUMBER_COMMA_SEPARATED2); // M
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=IFERROR(AVERAGE('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).'),0.00)',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // N
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // O
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_NUMBER_COMMA_SEPARATED2); // P
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=IFERROR(AVERAGE('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).'),0.00)',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // Q
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // R
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_NUMBER_COMMA_SEPARATED2); // S
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=IFERROR(AVERAGE('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).'),0.00)',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // T
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // U

                if ($hasHolidayHours) {
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                        '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                        ExcelHelper::FORMAT_NUMBER_COMMA_SEPARATED2); // V
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                        '=IFERROR(AVERAGE('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).'),0.00)',
                        ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // W
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                        '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                        ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // X
                }

                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // V or Y
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // W or Z
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt,
                    '=SUM('.Coordinate::stringFromColumnIndex($colCnt - 1).'2:'.Coordinate::stringFromColumnIndex($colCnt - 1).($rowCnt - 1).')',
                    ExcelHelper::FORMAT_CURRENCY_USD_SIMPLE); // X or AA

                ExcelHelper::boldTotalRow($worksheet);
            }

            ExcelHelper::topRowFormat($worksheet);

            $time = hrtime(true);
            $filename = Storage::disk('local')->path("exports/{$time}-Payroll Report {$this->filters['start_date']->format('Ymd')} - {$this->filters['end_date']->format('Ymd')}.xlsx");

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filename);

            $this->markLogAsSuccess();

            if (!empty($this->filters['product_id'])) {
                $campaign = DialerProduct::find($this->filters['product_id']);
            }

            Mail::send(new PayrollReport($this->filters['start_date'], $this->filters['end_date'], $filename, $campaign ?? null, $this->user));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        print $exception->getMessage().PHP_EOL.PHP_EOL;
        print $exception->getTraceAsString().PHP_EOL.PHP_EOL;

        $this->markLogAsFailure($exception);

        Mail::to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
            ->send(new JobStatus(
                jobType: self::class,
                status: 'Failure',
                subject: 'Payroll Report Export',
                error: $exception->getMessage(),
            ));
    }
}
