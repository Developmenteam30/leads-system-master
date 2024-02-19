<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentPerformance;
use App\Models\DialerPaymentType;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class PayrollExceptionsController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        AuditLog::createFromRequest($request, 'REPORT:PAYROLL-EXCEPTIONS', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $exceptions = collect([]);

        // Hours outside effective date range
        $rows = DialerAgentPerformance::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->joinEffectiveDates()
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agent_performances.file_date',
                'dialer_agents.agent_name',
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->whereNull('dialer_agent_effective_dates.id')
            ->where(function ($query) {
                $query->where('dialer_agent_performances.bonus_amount', '>', 0)
                    ->orWhere('dialer_agent_performances.billable_time_override', '>', 0);
            })
            ->with([
                'agent',
            ])
            ->get();

        $rows->each(function ($row) use (&$exceptions) {
            $exceptions->push([
                'agent' => $row->agent,
                'agent_id' => $row->agent_id,
                'agent_name' => $row->agent_name,
                'message' => "Hours logged for {$row->file_date} fall outside of effective date range.",
            ]);
        });

        // Missing rates
        $rows = DialerAgentPerformance::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->joinEffectiveDates()
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agent_effective_dates.payable_rate',
                'dialer_agent_effective_dates.billable_rate',
                'dialer_agent_effective_dates.bonus_rate',
                'dialer_agent_effective_dates.agent_type_id',
                'dialer_agent_effective_dates.payment_type_id',
                'dialer_agent_effective_dates.product_id',
                'dialer_agent_performances.file_date',
                'dialer_agent_performances.bonus_amount',
                'dialer_agent_performances.billable_time_override',
                'dialer_agents.agent_name',
                'dialer_agents.company_id',
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->whereNotNull('dialer_agent_effective_dates.id')
            ->where(function ($query) {
                $query->where('dialer_agent_performances.bonus_amount', '>', 0)
                    ->orWhere('dialer_agent_performances.billable_time_override', '>', 0);
            })
            ->with([
                'agent',
            ])
            ->groupBy('dialer_agents.id')
            ->get();

        $rows->each(function ($row) use (&$exceptions) {
            if (empty($row->payment_type_id)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No payment type set.",
                ]);
            }

            if (empty($row->product_id)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No campaign set.",
                ]);
            }

            if (empty($row->agent_type_id)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No agent type set.",
                ]);
            }

            if (in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::SALARY]) && is_null($row->billable_rate)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No billable rate set.",
                ]);
            }

            if (in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::SALARY]) && is_null($row->payable_rate)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No payable rate set.",
                ]);
            }

            if (in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS) && is_null($row->bonus_rate)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "No bonus rate set.",
                ]);
            } elseif (!in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS) && !is_null($row->bonus_rate)) {
                $exceptions->push([
                    'agent' => $row->agent,
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'message' => "Non-Acquiro agent has a bonus rate set.",
                ]);
            }

        });

        $allow_list = [
            'agent_id',
            'agent_name',
            'agent',
            'message',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Message', 'field' => 'message', 'displayFormat' => 'text'],
            ],
            'rows' => $exceptions->sortBy('agent_name')->values(),
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Payroll Exceptions {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");

    }
}
