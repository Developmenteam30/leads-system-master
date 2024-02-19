<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAccessArea;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentType;
use App\Models\DialerBillableTransfer;
use App\Models\DialerPaymentType;
use App\Models\User;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgentHoursController extends BaseController
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
            'company_ids' => 'nullable|string',
            'product_id' => 'nullable|string|exists:dialer_products,id',
            'view' => 'nullable|string|in:billable,payable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $company = null;

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS")) {
            if (empty($request->user()->company_id)) {
                return ErrorResponse::json('No company ID is set.', 401);
            } else {
                $company = Company::find($request->user()->company_id);
                if (!$company) {
                    return ErrorResponse::json('Company is not found.', 401);
                }
                $request->merge(['view' => $company->dialer_report_type]);
                if (Company::DIALER_REPORT_TYPE_BILLABLE !== $company->dialer_report_type) {
                    $request->merge(['company_ids' => $request->user()->company_id]);
                }
            }
        }

        $view = $request->input('view', Company::DIALER_REPORT_TYPE_BILLABLE);

        AuditLog::createFromRequest($request, 'REPORT:DIALER-AGENT-HOURS', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'company_ids' => $request->input('company_ids'),
            'product_id' => $request->input('product_id'),
            'view' => $view,
        ]);

        $rows = DialerAgentPerformance::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->leftJoin('dialer_agent_companies', 'dialer_agents.id', 'dialer_agent_companies.agent_id')
            ->joinEffectiveDates()
            ->select([
                'dialer_agents.agent_name',
                DB::raw("SUM(dialer_agent_performances.calls) AS calls"),
                DB::raw("ROUND(AVG(dialer_agent_performances.talk_pct),2) AS talk_pct"),
                DB::raw("ROUND(AVG(dialer_agent_performances.wait_pct),2) AS wait_pct"),
                DB::raw("ROUND(AVG(dialer_agent_performances.pause_pct),2) AS pause_pct"),
                DB::raw("ROUND(AVG(dialer_agent_performances.wrapup_pct),2) AS wrapup_pct"),
                DB::raw("SUM(dialer_agent_performances.talk_time) AS talk_time"),
                DB::raw("SUM(dialer_agent_performances.wait_time) AS wait_time"),
                DB::raw("SUM(dialer_agent_performances.pause_time) AS pause_time"),
                DB::raw("SUM(dialer_agent_performances.wrapup_time) AS wrapup_time"),
                DB::raw("SUM(dialer_agent_performances.total_time) AS total_time"),
                DB::raw("SUM(dialer_agent_performances.net_time) AS net_time"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",SUM(ROUND(dialer_agent_performances.billable_time_override/60,2)),NULL) AS billable_time"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",dialer_agent_effective_dates.{$view}_rate,NULL) AS billable_rate"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",SUM(dialer_agent_performances.{$view}_amount),NULL) AS billable_total"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::FINAL_TRANSFER.",SUM(dialer_agent_performances.billable_transfers_90),NULL) AS billable_transfers"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::FINAL_TRANSFER.",dialer_agent_effective_dates.{$view}_rate,NULL) AS billable_transfers_rate"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::AGENT." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::FINAL_TRANSFER.",ROUND(SUM((dialer_agent_performances.billable_transfers_90)*dialer_agent_effective_dates.{$view}_rate),2),NULL) AS billable_transfers_total"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::VISIBLE_EMPLOYEE." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",SUM(ROUND(dialer_agent_performances.billable_time_override/60,2)),NULL) AS qa_time"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::VISIBLE_EMPLOYEE." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",dialer_agent_effective_dates.{$view}_rate,NULL) AS qa_rate"),
                DB::raw("IF(dialer_agent_effective_dates.agent_type_id=".DialerAgentType::VISIBLE_EMPLOYEE." AND dialer_agent_effective_dates.payment_type_id=".DialerPaymentType::HOURLY.",SUM(dialer_agent_performances.{$view}_amount),NULL) AS qa_total"),
                DB::raw("SUM(dialer_agent_performances.transfers) AS transfers"),
                DB::raw("IF(dialer_agent_performances.calls=0, NULL, ROUND((SUM(dialer_agent_performances.transfers)/SUM(dialer_agent_performances.calls))*100,2)) AS transfer_pct"),
                DB::raw("SUM(dialer_agent_performances.under_6_min) AS under_6_min"),
                DB::raw("SUM(dialer_agent_performances.over_7_min) AS over_7_min"),
                DB::raw("SUM(dialer_agent_performances.over_20_min) AS over_20_min"),
                DB::raw("SUM(dialer_agent_performances.over_60_min) AS over_60_min"),
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('dialer_agent_effective_dates.product_id', $request->input('product_id'));
            })
            ->when(!empty($company) && Company::DIALER_REPORT_TYPE_BILLABLE === $company->dialer_report_type, function ($query) use ($company) {
                $query->where(function ($query) use ($company) {
                    $query->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
                        ->orWhere(function ($query) use ($company) {
                            $query->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::VISIBLE_EMPLOYEE])
                                ->where('dialer_agent_companies.company_id', $company->idCompany);
                        });
                });
            })
            ->whereNotNull('dialer_agents.company_id')
            ->where(function ($query) use ($view) {
                $query->where("dialer_agent_performances.{$view}_amount", '>', 0)
                    ->orWhere('dialer_agent_performances.bonus_amount', '>', 0)
                    ->orWhere('dialer_agent_performances.billable_transfers', '>', 0)
                    ->orWhere('dialer_agent_performances.calls', '>', 0);
            })
            ->orderBy('dialer_agent_effective_dates.agent_type_id')
            ->orderBy('dialer_agents.agent_name')
            ->groupBy('dialer_agent_performances.agent_id')
            ->get();

        $billableTransfers = DialerBillableTransfer::query()
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('company_id', explode(',', $request->input('company_ids')));
            })
            ->sum('payable' === $view ? 'billable_transfers_120' : 'billable_transfers_90');

        $billableTransferRate = 'payable' === $view ? DialerBillableTransfer::PAYABLE_RATE : DialerBillableTransfer::BILLABLE_RATE;

        $datatable = [
            'columns' => array_merge([
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Calls', 'field' => 'calls', 'displayFormat' => 'integer'],
                ['label' => 'Talk %', 'field' => 'talk_pct', 'displayFormat' => 'percentage'],
                ['label' => 'Wait %', 'field' => 'wait_pct', 'displayFormat' => 'percentage'],
                ['label' => 'Pause %', 'field' => 'pause_pct', 'displayFormat' => 'percentage'],
                ['label' => 'Wrap Up %', 'field' => 'wrapup_pct', 'displayFormat' => 'percentage'],
                ['label' => 'Talk Time', 'field' => 'talk_time', 'displayFormat' => 'sec2time'],
                ['label' => 'Wait Time', 'field' => 'wait_time', 'displayFormat' => 'sec2time'],
                ['label' => 'Pause Time', 'field' => 'pause_time', 'displayFormat' => 'sec2time'],
                ['label' => 'Wrap Up Time', 'field' => 'wrapup_time', 'displayFormat' => 'sec2time'],
                ['label' => 'Total Time', 'field' => 'total_time', 'displayFormat' => 'sec2time'],
                ['label' => 'Talk+Wait+Wrap Up', 'field' => 'net_time', 'displayFormat' => 'sec2time'],
            ], $rows->sum('billable_time') > 0 ? [
                ['label' => ucfirst($view).' Time', 'field' => 'billable_time', 'displayFormat' => 'number'],
                ['label' => ucfirst($view).' Rate', 'field' => 'billable_rate', 'displayFormat' => 'currency'],
                ['label' => ucfirst($view).' Total', 'field' => 'billable_total', 'displayFormat' => 'currency'],
            ] : [], $billableTransfers > 0 ? [
                ['label' => 'Billable Transfers', 'field' => 'billable_transfers', 'displayFormat' => 'integer'],
                ['label' => 'BT Rate', 'field' => 'billable_transfers_rate', 'displayFormat' => 'currency'],
                ['label' => 'BT Total', 'field' => 'billable_transfers_total', 'displayFormat' => 'currency'],
            ] : [], $rows->sum('qa_time') > 0 ? [
                ['label' => 'QA Time', 'field' => 'qa_time', 'displayFormat' => 'number'],
                ['label' => 'QA Rate', 'field' => 'qa_rate', 'displayFormat' => 'currency'],
                ['label' => 'QA Total', 'field' => 'qa_total', 'displayFormat' => 'currency'],
            ] : [], [
                ['label' => 'Transfers', 'field' => 'transfers', 'displayFormat' => 'integer'],
                ['label' => 'Transfer %', 'field' => 'transfer_pct'],
                ['label' => '<6 Min', 'field' => 'under_6_min', 'displayFormat' => 'integer'],
                ['label' => '7 Min+', 'field' => 'over_7_min', 'displayFormat' => 'integer'],
                ['label' => '20 Min+', 'field' => 'over_20_min', 'displayFormat' => 'integer'],
                ['label' => '1 Hour+', 'field' => 'over_60_min', 'displayFormat' => 'integer'],
            ]),
            'rows' => $rows,
            'totals' => [
                [
                    'agent_name' => 'TOTALS',
                    'calls' => $rows->sum('calls'),
                    'talk_pct' => round($rows->avg('talk_pct'), 2),
                    'wait_pct' => round($rows->avg('wait_pct'), 2),
                    'pause_pct' => round($rows->avg('pause_pct'), 2),
                    'wrapup_pct' => round($rows->avg('wrapup_pct'), 2),
                    'talk_time' => $rows->sum('talk_time'),
                    'wait_time' => $rows->sum('wait_time'),
                    'pause_time' => $rows->sum('pause_time'),
                    'wrapup_time' => $rows->sum('wrapup_time'),
                    'total_time' => $rows->sum('total_time'),
                    'net_time' => $rows->sum('net_time'),
                    'billable_time' => round($rows->sum('billable_time'), 2),
                    'billable_rate' => round($rows->avg('billable_rate'), 2),
                    'billable_total' => round($rows->sum('billable_total'), 2),
                    'billable_transfers' => $billableTransfers,
                    'billable_transfers_rate' => $billableTransferRate,
                    'billable_transfers_total' => round($billableTransfers * $billableTransferRate, 2),
                    'qa_time' => round($rows->sum('qa_time'), 2),
                    'qa_rate' => round($rows->avg('qa_rate'), 2),
                    'qa_total' => round($rows->sum('qa_total'), 2),
                    'transfers' => $rows->sum('transfers'),
                    'transfer_pct' => round($rows->avg('transfer_pct'), 2),
                    'under_6_min' => $rows->sum('under_6_min'),
                    'over_7_min' => $rows->sum('over_7_min'),
                    'over_20_min' => $rows->sum('over_20_min'),
                    'over_60_min' => $rows->sum('over_60_min'),
                ],
            ],
        ];

        return DataTableFields::displayOrExport($datatable, collect($datatable['columns'])->pluck('field')->toArray(), $request,
            "Agent Hours {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }
}
