<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Helpers\Numbers;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentEvaluation;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentPip;
use App\Models\DialerLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerformanceTrackerController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|string|exists:dialer_agents,id',
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'evaluation_id' => 'bail|nullable|string|exists:dialer_agent_evaluations,id',
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

        AuditLog::createFromRequest($request, 'REPORT:DIALER-AGENT-PERFORMANCE-TRACKER', [
            'agent_id' => $request->input('agent_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        $diff = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $startDate->subDays($diff);
        $previousEndDate = $previousStartDate->addDays($diff - 1);

        $agent_id = $request->input('agent_id');
        $agent = DialerAgent::query()
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->leftJoin('dialer_agents AS manager', 'manager.id', 'dialer_teams.manager_agent_id')
            ->select([
                'dialer_agents.id',
                'dialer_agents.agent_name',
                DB::raw('dialer_teams.name AS team_name'),
                DB::raw('manager.agent_name AS manager_name'),
            ])
            ->where('dialer_agents.id', $agent_id)
            ->first()
            ->append([
                'effectiveHireDate',
            ]);

        $effectiveDateRow = $agent->getEffectiveValuesForDateRange($startDate, $endDate);
        if (empty($effectiveDateRow) || empty($effectiveDateRow->product_id)) {
            return ErrorResponse::json("Cannot find an active campaign for this agent during this time period.", 400);
        }

        $agentIsTraining = $agent->isTrainingForDateRange($startDate, $endDate);

        $agents = DialerAgent::query()
            ->isActiveForDateRange($startDate, $endDate)
            ->where('dialer_agent_effective_dates.product_id', $effectiveDateRow->product_id)
            ->select('dialer_agents.id')
            ->with([
                'effectiveDates',
            ])
            ->get();


        // Filter out agents based on training status
        $agents = $agents->filter(function ($agent) use ($agentIsTraining, $startDate, $endDate) {
            return $agent->isTrainingForDateRange($startDate, $endDate) === $agentIsTraining;
        });

        $columns = [
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
        ];

        $current_values = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->select($columns)
            ->whereBetween('dialer_agent_performances.file_date', [
                $startDate,
                $endDate,
            ])
            ->whereIn('dialer_agent_performances.agent_id', $agents->pluck('id'))
            ->whereNotNull('dialer_agent_performances.calls')
            ->get();

        $previous_values = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->select($columns)
            ->whereBetween('dialer_agent_performances.file_date', [
                $previousStartDate,
                $previousEndDate,
            ])
            ->whereIn('dialer_agent_performances.agent_id', $agents->pluck('id'))
            ->whereNotNull('dialer_agent_performances.calls')
            ->get();

        $evaluation = ['notes' => ''];
        if ($request->filled('evaluation_id')) {
            $record = DialerAgentEvaluation::find($request->input('evaluation_id'));
            if (!empty($record)) {
                $evaluation = [
                    'id' => $record->id,
                    'reporter_name' => $record->reporter?->agent_name,
                    'notes' => $record->notes,
                    'created_at' => $record->created_at->setTimezone(config('settings.timezone.local'))->format('Y-m-d'),
                    'writeup_id' => $record->writeup_id,
                    'writeup_flag' => !empty($record->writeup_id) ? 'Yes' : 'No',
                ];
            }
        }

        $pip = DialerAgentPip::query()
            ->where('agent_id', $agent_id)
            ->whereBetween('start_date', [
                $startDate,
                $endDate,
            ])
            ->count();

        return [
            'agent' => $agent,
            'current' => self::agentCalculations($agent_id, $current_values, $startDate, $endDate),
            'current_average' => self::averageCalculations($current_values),
            'previous' => self::agentCalculations($agent_id, $previous_values, $previousStartDate, $previousEndDate),
            'previous_average' => self::averageCalculations($previous_values),
            'current_start_date' => $startDate->format('n/j/y'),
            'current_end_date' => $endDate->format('n/j/y'),
            'previous_start_date' => $previousStartDate->format('n/j/y'),
            'previous_end_date' => $previousEndDate->format('n/j/y'),
            'evaluation' => $evaluation,
            'training' => $agentIsTraining,
            'pip' => $pip,
        ];
    }

    private function agentCalculations($agent_id, $values, Carbon|CarbonImmutable $startDate, Carbon|CarbonImmutable $endDate): array
    {
        $calls = $values->where('agent_id', $agent_id)->sum('calls');
        $transfers = $values->where('agent_id', $agent_id)->sum('transfers');
        $billable_transfers = $values->where('agent_id', $agent_id)->sum('billable_transfers');
        $successful_transfers_bill_time = $values->where('agent_id', $agent_id)->sum('successful_transfers_bill_time');
        $under_5_min = $values->where('agent_id', $agent_id)->sum('under_5_min');
        $billable_time = $values->where('agent_id', $agent_id)->sum('billable_time');
        $billable_rate = $values->where('agent_id', $agent_id)->avg('billable_rate');
        $over_60_min = $values->where('agent_id', $agent_id)->sum('over_60_min');
        $training = $values->where('agent_id', $agent_id)->sum('in_training') > 0 ? 1 : 0;

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
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    private function averageCalculations($values): array
    {
        $agents_count = $values->unique('agent_id')->count();

        $calls = Numbers::averageAndRound($values->sum('calls'), $agents_count, 0);
        $transfers = Numbers::averageAndRound($values->sum('transfers'), $agents_count, 0);
        $billable_transfers = Numbers::averageAndRound($values->sum('billable_transfers'), $agents_count, 0);
        $successful_transfers_bill_time = Numbers::averageAndRound($values->sum('successful_transfers_bill_time'), $values->sum('transfers'), 2);
        $under_5_min = Numbers::averageAndRound($values->sum('under_5_min'), $agents_count, 0);
        $billable_time = Numbers::averageAndRound($values->sum('billable_time'), $agents_count, 1);
        $billable_rate = $values->avg('billable_rate');
        $over_60_min = Numbers::averageAndRound($values->sum('over_60_min'), $agents_count);

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

    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function lowestTransfers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'nullable|string|exists:dialer_agents,id',
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $rows = DialerLog::query()
            ->select([
                'call_id',
                'time_stamp',
                'number_dialed',
                'bill_time',
                'recordings',
            ])
            ->where('agent_id', $request->input('agent_id'))
            ->whereBetween('time_stamp', [
                $startDate,
                $endDate,
            ])
            ->whereIn('status', ['ST', 'STSSDI'])
            ->whereRaw('TIME_TO_SEC(bill_time) < 60*5')
            ->orderBy('bill_time', 'DESC')
            ->limit(5)
            ->get();

        $rows->each(function ($row) {

            $row->recording_link = !empty($row->recordings) ? sprintf('<a class="btn btn-primary" href="%s">Listen</a>', $row->recordings) : '';

            return $row;
        });

        $allow_list = array_merge([
            'call_id',
            'time_stamp',
            'number_dialed',
            'bill_time',
            'recording_link',
        ]);

        $datatable = [
            'columns' => [
                ['label' => 'Call ID', 'field' => 'call_id'],
                ['label' => 'Date', 'field' => 'time_stamp'],
                ['label' => 'Phone', 'field' => 'number_dialed'],
                ['label' => 'Call Length', 'field' => 'bill_time'],
                ['label' => 'Recording', 'field' => 'recording_link'],
            ],
            'rows' => $rows,
        ];

        return DataTableFields::getByAllowList($datatable, $allow_list);
    }
}
