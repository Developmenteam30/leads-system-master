<?php

namespace App\Http\Controllers\Api\Reports;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentType;
use App\Models\DialerBillableTransfer;
use App\Models\DialerPaymentType;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends BaseController
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
            'view' => 'nullable|string|in:billable,payable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $view = $request->input('view', Company::DIALER_REPORT_TYPE_BILLABLE);

        AuditLog::createFromRequest($request, 'REPORT:DASHBOARD', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'company_ids' => $request->input('company_ids'),
            'view' => $view,
        ]);

        $billableTime = DialerAgentPerformance::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->joinEffectiveDates()
            ->select([
                DB::raw('DAYOFWEEK(file_date) AS day_of_week'),
                DB::raw("SUM(ROUND(dialer_agent_performances.billable_time_override/60,2)) AS billable_time"),
                DB::raw("AVG(dialer_agent_effective_dates.{$view}_rate) AS billable_rate"),
                DB::raw("SUM(dialer_agent_performances.billable_transfers) AS billable_transfers"),
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->where('dialer_agent_effective_dates.agent_type_id', DialerAgentType::AGENT)
            ->where('dialer_agent_effective_dates.payment_type_id', DialerPaymentType::HOURLY)
            ->groupByRaw('DAYOFWEEK(file_date)')
            ->get();

        $timeChart = [];
        for ($i = 0; $i <= 6; $i++) {
            $timeChart[$i] = floatval($billableTime->firstWhere('day_of_week', $i + 1)['billable_time'] ?? 0);
        }

        $transfersChart = [];
        for ($i = 0; $i <= 6; $i++) {
            $transfersChart[$i] = floatval($billableTime->firstWhere('day_of_week', $i + 1)['billable_transfers'] ?? 0);
        }

        $billableTransferRate = 'payable' === $view ? DialerBillableTransfer::PAYABLE_RATE : DialerBillableTransfer::BILLABLE_RATE;

        return [
            'billable_time' => [
                'labels' => [
                    "Sun",
                    "Mon",
                    "Tue",
                    "Wed",
                    "Thu",
                    "Fri",
                    "Sat",
                ],
                'datasets' => [
                    [
                        'label' => 'Hours',
                        'data' => $timeChart,
                    ],
                ],
            ],
            'billable_transfers' => [
                'labels' => [
                    "Sun",
                    "Mon",
                    "Tue",
                    "Wed",
                    "Thu",
                    "Fri",
                    "Sat",
                ],
                'datasets' => [
                    [
                        'label' => 'Transfers',
                        'data' => $transfersChart,
                    ],
                ],
            ],
            'totals' => [
                'billable_time' => round($billableTime->sum('billable_time'), 2),
                'billable_rate' => round($billableTime->avg('billable_rate'), 2),
                'billable_total' => round(round($billableTime->sum('billable_time'), 2) * round($billableTime->avg('billable_rate'), 2), 2),
                'billable_transfers' => $billableTime->sum('billable_transfers'),
                'billable_transfers_rate' => $billableTransferRate,
                'billable_transfers_total' => round($billableTime->sum('billable_transfers') * $billableTransferRate, 2),
            ],
        ];
    }
}
