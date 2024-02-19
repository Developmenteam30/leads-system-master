<?php

namespace App\Jobs;

use App\Datasets\PayrollReportDataset;
use App\Helpers\Numbers;
use App\Mail\StatsReportMail;
use App\Models\Company;
use App\Models\DialerAgentType;
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

class SendStatsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

    protected Carbon $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $logId = null)
    {
        $this->date = $date;
        $this->logId = $logId;
        $this->subject = 'Stats Report: '.$this->date->format('Y-m-d');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $this->markLogAsSuccess();

            $agent_billable = PayrollReportDataset::getWeeklyValues([
                'start_date' => $this->date,
                'end_date' => $this->date,
                'view' => Company::DIALER_REPORT_TYPE_BILLABLE,
                'agent_type_ids' => [DialerAgentType::AGENT],
            ]);

            $employee_billable = PayrollReportDataset::getWeeklyValues([
                'start_date' => $this->date,
                'end_date' => $this->date,
                'view' => Company::DIALER_REPORT_TYPE_BILLABLE,
                'agent_type_ids' => [DialerAgentType::VISIBLE_EMPLOYEE],
            ]);

            $agent_payable = PayrollReportDataset::getWeeklyValues([
                'start_date' => $this->date,
                'end_date' => $this->date,
                'view' => Company::DIALER_REPORT_TYPE_PAYABLE,
                'agent_type_ids' => [DialerAgentType::AGENT],
            ]);

            $employee_payable = PayrollReportDataset::getWeeklyValues([
                'start_date' => $this->date,
                'end_date' => $this->date,
                'view' => Company::DIALER_REPORT_TYPE_PAYABLE,
                'agent_type_ids' => [DialerAgentType::VISIBLE_EMPLOYEE],
            ]);

            $stats = [
                'agent_billable_hours' => Numbers::roundAndFormat($agent_billable->sum('total_hours')),
                'agent_billable_rate' => Numbers::roundAndFormatCurrency($agent_billable->sum('total_hours') > 0 ? $agent_billable->sum('payroll_amount') / $agent_billable->sum('total_hours') : 0),
                'agent_billable_total' => Numbers::roundAndFormatCurrency($agent_billable->sum('payroll_amount')),
                'agent_payable_hours' => Numbers::roundAndFormat($agent_payable->sum('total_hours')),
                'agent_payable_rate' => Numbers::roundAndFormatCurrency($agent_payable->sum('total_hours') > 0 ? $agent_payable->sum('payroll_amount') / $agent_payable->sum('total_hours') : 0),
                'agent_payable_total' => Numbers::roundAndFormatCurrency($agent_payable->sum('payroll_amount')),
                'agent_gross_profit' => Numbers::roundAndFormatCurrency($agent_billable->sum('payroll_amount') - $agent_payable->sum('payroll_amount')),
                'employee_billable_hours' => Numbers::roundAndFormat($employee_billable->sum('total_hours')),
                'employee_billable_rate' => Numbers::roundAndFormatCurrency($employee_billable->sum('total_hours') > 0 ? $employee_billable->sum('payroll_amount') / $employee_billable->sum('total_hours') : 0),
                'employee_billable_total' => Numbers::roundAndFormatCurrency($employee_billable->sum('payroll_amount')),
                'employee_payable_hours' => Numbers::roundAndFormat($employee_payable->sum('total_hours')),
                'employee_payable_rate' => Numbers::roundAndFormatCurrency($employee_payable->sum('total_hours') > 0 ? $employee_payable->sum('payroll_amount') / $employee_payable->sum('total_hours') : 0),
                'employee_payable_total' => Numbers::roundAndFormatCurrency($employee_payable->sum('payroll_amount')),
                'employee_gross_profit' => Numbers::roundAndFormatCurrency($employee_billable->sum('payroll_amount') - $employee_payable->sum('payroll_amount')),
            ];

            Mail::send(new StatsReportMail($this->date, $stats));
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
