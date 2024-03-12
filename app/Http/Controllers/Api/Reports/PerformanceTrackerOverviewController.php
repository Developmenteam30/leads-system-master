<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Helpers\Numbers;
use App\Models\AuditLog;
use App\Models\DialerAgentPerformance;
use App\Responses\ErrorResponse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerformanceTrackerOverviewController extends BaseController
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
            'product_id' => 'nullable|string|exists:dialer_products,id',
            'company_ids' => 'nullable|string',
            'search' => 'bail|string|nullable',
            'statuses' => 'bail|string|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = CarbonImmutable::parse($request->input('start_date'));
        $endDate = CarbonImmutable::parse($request->input('end_date'));

        if ($startDate->gt($endDate)) {
            return ErrorResponse::json('The end date must be after the start date.', 400);
        }

        AuditLog::createFromRequest($request, 'REPORT:DIALER-PERFORMANCE-TRACKER-OVERVIEW', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'product_id' => $request->input('product_id'),
            'company_ids' => $request->input('company_ids'),
            'search' => $request->input('search'),
            'statuses' => $request->input('statuses'),
        ]);

        $statuses = explode(',', $request->input('statuses', ''));

        $rows = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->leftJoin('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->leftJoin('dialer_agent_terminations', 'dialer_agents.id', 'dialer_agent_terminations.agent_id')
            ->select([
                'dialer_agent_terminations.created_at AS termination_date',
                'dialer_agent_performances.agent_id',
                'dialer_agents.agent_name',
                DB::raw('SUM(dialer_agent_performances.calls) AS calls'),
                DB::raw('SUM(dialer_agent_performances.transfers) AS transfers'),
                DB::raw('SUM(dialer_agent_performances.billable_transfers) AS billable_transfers'),
                DB::raw('SUM(dialer_agent_performances.successful_transfers_bill_time) AS successful_transfers_bill_time'),
                DB::raw('SUM(dialer_agent_performances.under_5_min) AS under_5_min'),
                DB::raw('SUM(dialer_agent_performances.billable_time_override) AS billable_time_override'),
                DB::raw('SUM(IFNULL(ROUND(dialer_agent_performances.billable_time_override/60,2),0)) AS billable_time'),
                'dialer_agent_effective_dates.billable_rate',
                // DB::raw("CONCAT('<h5><strong>', dialer_agent_effective_dates.end_date, '</strong></h5>') AS end_date"),
                'dialer_agent_effective_dates.end_date',
                DB::raw('SUM(dialer_agent_performances.billable_time_override) AS billable_time_override'),
                DB::raw('SUM(dialer_agent_performances.over_60_min) AS over_60_min'),
                DB::raw("SUM(IFNULL(dialer_agent_performances.forced_pause_cnt,0)) AS forced_pause_cnt"),
                DB::raw("SUM(IFNULL(dialer_agent_performances.forced_logout_cnt,0)) AS forced_logout_cnt"),
            ])
            ->whereBetween('dialer_agent_performances.file_date', [
                $startDate,
                $endDate,
            ])
            ->whereNotNull('dialer_agent_performances.calls')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('dialer_agents.agent_name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('dialer_agent_effective_dates.product_id', $request->input('product_id'));
            })
            ->with([
                'agent',
                'agent.effectiveDates',
            ])
            ->groupBy('dialer_agent_performances.agent_id')
            ->orderBy('dialer_agents.agent_name')
            ->get();

        $rows->map(function ($row) {

            $row->conversion_rate = Numbers::averagePercentageAndRound($row->transfers, $row->calls, 1);
            $row->avg_transfer_duration = Numbers::averageAndRound($row->successful_transfers_bill_time, $row->transfers, 1);
            $row->under_5min_pct = Numbers::averagePercentageAndRound($row->under_5_min, $row->transfers, 1);
            $row->bt_per_bh = Numbers::averageAndRound($row->billable_transfers, $row->billable_time, 1);
            $row->cost_per_bt = round(Numbers::average($row->billable_time, $row->billable_transfers) * $row->billable_rate, 2);
            if($row->termination_date){
                $row->effectiveHireDate = '<strong style="color: red; font-weight: bold;">'.$row->agent->effectiveHireDate.'</strong>';
            }else{
                $row->effectiveHireDate = $row->agent->effectiveHireDate;
            }

            $row->cost_per_sale = round(Numbers::average($row->billable_time, $row->over_60_min) * $row->billable_rate, 2);
            if (!$row->cost_per_sale) {
                $row->cost_per_sale = round($row->billable_time * $row->billable_rate, 2);
            }

            return $row;
        });

        if (sizeof($statuses) == 1 && in_array('1', $statuses)) {
            $rows = $rows->filter(function ($row) {
                return empty($row->agent->latestActiveEffectiveDate->end_date);
            })->values();
        } elseif (sizeof($statuses) == 1 && in_array('0', $statuses)) {
            $rows = $rows->filter(function ($row) {
                return !empty($row->agent->latestActiveEffectiveDate->end_date);
            })->values();
        }

        $allow_list = [
            'termination_date',
            'agent_id',
            'agent_name',
            'calls',
            'transfers',
            'conversion_rate',
            'avg_transfer_duration',
            'under_5_min',
            'under_5min_pct',
            'billable_time',
            'billable_transfers',
            'bt_per_bh',
            'cost_per_bt',
            'over_60_min',
            'cost_per_sale',
            'effectiveHireDate',
            'forced_pause_cnt',
            'forced_logout_cnt',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name', 'fixed' => true],
                ['label' => 'Hire Date', 'field' => 'effectiveHireDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Calls', 'field' => 'calls', 'displayFormat' => 'integer'],
                ['label' => 'STs', 'field' => 'transfers', 'displayFormat' => 'integer'],
                ['label' => 'Conversion Rate', 'field' => 'conversion_rate', 'displayFormat' => 'percentage'],
                ['label' => 'Avg Transfer Duration', 'field' => 'avg_transfer_duration', 'displayFormat' => 'sec2time'],
                ['label' => 'STs under 5 Mins', 'field' => 'under_5_min', 'displayFormat' => 'integer'],
                ['label' => '% STs under 5 Mins', 'field' => 'under_5min_pct', 'displayFormat' => 'percentage'],
                ['label' => 'Billable Hours', 'field' => 'billable_time', 'displayFormat' => 'number'],
                ['label' => 'BTs', 'field' => 'billable_transfers', 'displayFormat' => 'integer'],
                ['label' => 'BTs per Billable Hour', 'field' => 'bt_per_bh', 'displayFormat' => 'number'],
                ['label' => 'Cost per BT', 'field' => 'cost_per_bt', 'displayFormat' => 'currency'],
                ['label' => 'Estimated Sales', 'field' => 'over_60_min', 'displayFormat' => 'integer'],
                ['label' => 'Estimated CPS', 'field' => 'cost_per_sale', 'displayFormat' => 'currency'],
                ['label' => 'Forced Pauses', 'field' => 'forced_pause_cnt', 'displayFormat' => 'integer'],
                ['label' => 'Forced Logouts', 'field' => 'forced_logout_cnt', 'displayFormat' => 'integer'],
            ],
            'rows' => $rows,
            'totals' => [
                [
                    'agent_name' => 'TOTALS',
                    'calls' => $rows->sum('calls'),
                    'transfers' => $rows->sum('transfers'),
                    'conversion_rate' => Numbers::averagePercentageAndRound($rows->sum('transfers'), $rows->sum('calls'), 1),
                    'avg_transfer_duration' => Numbers::averageAndRound($rows->sum('successful_transfers_bill_time'), $rows->sum('transfers'), 1),
                    'under_5_min' => $rows->sum('under_5_min'),
                    'under_5min_pct' => Numbers::averagePercentageAndRound($rows->sum('under_5_min'), $rows->sum('transfers'), 1),
                    'billable_time' => $rows->sum('billable_time'),
                    'billable_transfers' => $rows->sum('billable_transfers'),
                    'bt_per_bh' => Numbers::averageAndRound($rows->sum('billable_transfers'), $rows->sum('billable_time'), 1),
                    'cost_per_bt' => round(Numbers::average($rows->sum('billable_time'), $rows->sum('billable_transfers')) * $rows->avg('billable_rate'), 2),
                    'over_60_min' => $rows->sum('over_60_min'),
                    'cost_per_sale' => round(Numbers::average($rows->where('over_60_min', '>', 0)->sum('billable_time'), $rows->sum('over_60_min')) * $rows->avg('billable_rate'), 2),
                    'forced_pause_cnt' => $rows->sum('forced_pause_cnt'),
                    'forced_logout_cnt' => $rows->sum('forced_logout_cnt'),
                ],
            ],
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Performance Tracker Overview.xlsx");
    }
}
