<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Helpers\Numbers;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PerformanceTrackerTeamController extends BaseController
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

        $startDate = CarbonImmutable::parse($request->input('start_date'));
        $endDate = CarbonImmutable::parse($request->input('end_date'));

        if ($startDate->gt($endDate)) {
            return ErrorResponse::json('The end date must be after the start date.', 400);
        }

        $team_id = ($request->input('team_id')) ? $request->input('team_id') : $request->user()->team_id;
        AuditLog::createFromRequest($request, 'REPORT:DIALER-AGENT-PERFORMANCE-TRACKER-TEAM', [
            'team_id' => $team_id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        $diff = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $startDate->subDays($diff);
        $previousEndDate = $previousStartDate->addDays($diff - 1);

        $current_values = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agent_performances.calls',
                'dialer_agent_performances.transfers',
                'dialer_agent_performances.billable_transfers',
                'dialer_agent_performances.successful_transfers_bill_time',
                'dialer_agent_performances.under_5_min',
                'dialer_agent_performances.billable_time_override',
                DB::raw('IFNULL(ROUND(dialer_agent_performances.billable_time_override/60,2),0) AS billable_time'),
                'dialer_agent_effective_dates.billable_rate',
                'dialer_agent_performances.over_60_min',
                'dialer_agent_performances.payable_training',
            ])
            ->with('agent', function ($query) {
                return $query->select(['id', 'team_id']);
            })
            ->whereBetween('dialer_agent_performances.file_date', [
                $startDate,
                $endDate,
            ])
            ->whereNotNull('dialer_agent_performances.calls')
            ->get();

        $previous_values = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agent_performances.calls',
                'dialer_agent_performances.transfers',
                'dialer_agent_performances.billable_transfers',
                'dialer_agent_performances.successful_transfers_bill_time',
                'dialer_agent_performances.under_5_min',
                'dialer_agent_performances.billable_time_override',
                DB::raw('IFNULL(ROUND(dialer_agent_performances.billable_time_override/60,2),0) AS billable_time'),
                'dialer_agent_effective_dates.billable_rate',
                'dialer_agent_performances.over_60_min',
                'dialer_agent_performances.payable_training',
            ])
            ->with([
                'agent' => function ($query) {
                    $query->select(['id', 'team_id']);
                },
            ])
            ->whereBetween('dialer_agent_performances.file_date', [
                $previousStartDate,
                $previousEndDate,
            ])
            ->whereNotNull('dialer_agent_performances.calls')
            ->get();

        return [
            'current' => self::teamCalculations($team_id, $current_values, $startDate, $endDate),
            'current_average' => self::averageCalculations($team_id, $current_values),
            'previous' => self::teamCalculations($team_id, $previous_values, $previousStartDate, $previousEndDate),
            'previous_average' => self::averageCalculations($team_id, $previous_values),
            'current_start_date' => $startDate->format('n/j/y'),
            'current_end_date' => $endDate->format('n/j/y'),
            'previous_start_date' => $previousStartDate->format('n/j/y'),
            'previous_end_date' => $previousEndDate->format('n/j/y'),
        ];
    }

    private function teamCalculations($team_id, $values, Carbon|CarbonImmutable $startDate, Carbon|CarbonImmutable $endDate)
    {
        $calls = $values->where('agent.team_id', $team_id)->sum('calls');
        $transfers = $values->where('agent.team_id', $team_id)->sum('transfers');
        $billable_transfers = $values->where('agent.team_id', $team_id)->sum('billable_transfers');
        $successful_transfers_bill_time = $values->where('agent.team_id', $team_id)->sum('successful_transfers_bill_time');
        $under_5_min = $values->where('agent.team_id', $team_id)->sum('under_5_min');
        $billable_time = $values->where('agent.team_id', $team_id)->sum('billable_time');
        $billable_rate = $values->where('agent.team_id', $team_id)->avg('billable_rate');
        $over_60_min = $values->where('agent.team_id', $team_id)->sum('over_60_min');
        $training = $values->where('agent.team_id', $team_id)->sum('payable_training') > 0 ? 1 : 0;

        return [
            'calls' => $calls,
            'transfers' => $transfers,
            'conversion_rate' => Numbers::averagePercentageAndRound($transfers, $calls, 1),
            'billable_transfers' => $billable_transfers,
            'successful_transfers_bill_time' => Numbers::averageAndRound($successful_transfers_bill_time, $transfers, 1),
            'under_5_min' => $under_5_min,
            'under_5min_pct' => Numbers::averagePercentageAndRound($under_5_min, $transfers, 1),
            'billable_time' => $billable_time,
            'bt_per_bh' => Numbers::averageAndRound($billable_transfers, $billable_time, 1),
            'cost_per_bt' => round(Numbers::average($billable_time, $billable_transfers) * $billable_rate, 2),
            'cost_per_sale' => round(Numbers::average($billable_time, $over_60_min) * $billable_rate, 2),
            'over_60_min' => $over_60_min,
            'training' => $training,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    private function averageCalculations($team_id, $values)
    {
        $teams_count = $values->unique('agent.team_id')->count();

        $calls = Numbers::averageAndRound($values->sum('calls'), $teams_count, 0);
        $transfers = Numbers::averageAndRound($values->sum('transfers'), $teams_count, 0);
        $billable_transfers = Numbers::averageAndRound($values->sum('billable_transfers'), $teams_count, 0);
        $successful_transfers_bill_time = Numbers::averageAndRound($values->sum('successful_transfers_bill_time'), $values->sum('transfers'), 2);
        $under_5_min = Numbers::averageAndRound($values->sum('under_5_min'), $teams_count, 0);
        $billable_time = Numbers::averageAndRound($values->sum('billable_time'), $teams_count, 1);
        $billable_rate = $values->avg('billable_rate');
        $over_60_min = Numbers::averageAndRound($values->sum('over_60_min'), $teams_count);

        return [
            'calls' => $calls,
            'transfers' => $transfers,
            'conversion_rate' => Numbers::averagePercentageAndRound($transfers, $calls, 1),
            'billable_transfers' => $billable_transfers,
            'successful_transfers_bill_time' => $successful_transfers_bill_time,
            'under_5_min' => $under_5_min,
            'under_5min_pct' => Numbers::averagePercentageAndRound($under_5_min, $transfers, 1),
            'billable_time' => $billable_time,
            'bt_per_bh' => Numbers::averageAndRound($billable_transfers, $billable_time, 1),
            'cost_per_bt' => round(Numbers::average($billable_time, $billable_transfers) * $billable_rate, 2),
            'cost_per_sale' => round(Numbers::average($billable_time, $over_60_min) * $billable_rate, 2),
            'billable_rate' => $billable_rate,
            'over_60_min' => $over_60_min,
        ];
    }
}
