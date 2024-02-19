<?php

namespace App\Http\Controllers\Api\Reports;

use App\Models\AuditLog;
use App\Models\DialerAgentPerformance;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends BaseController
{
    const CELL_FORMAT = '<!--SORT:%s--><div class="attendance-cell attendance-cell-%s"><span class="attendance-calls">%s</span> <span class="attendance-wrapup">%s</span></div>';

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
            'statuses' => 'bail|string|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        AuditLog::createFromRequest($request, 'REPORT:ATTENDANCE', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'company_ids' => $request->input('company_ids'),
            'statuses' => $request->input('statuses'),
        ]);

        $statuses = explode(',', $request->input('statuses', ''));

        $rows = DialerAgentPerformance::query()
            ->joinEffectiveDates()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agents.agent_name',
                DB::raw("DATE_FORMAT(dialer_agent_performances.file_date,'%e') AS file_date"),
                DB::raw("SUM(dialer_agent_performances.calls) AS calls"),
                DB::raw("SUM(ROUND(dialer_agent_performances.billable_time_override/60,2)) AS billable_time"),
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->with([
                'agent',
                'agent.effectiveDates',
            ])
            ->having('calls', '>', 0)
            ->orderBy('dialer_agents.agent_name')
            ->groupBy('dialer_agent_performances.agent_id', 'dialer_agent_performances.file_date')
            ->get();

        if (sizeof($statuses) == 1 && in_array('1', $statuses)) {
            $rows = $rows->filter(function ($row) {
                return empty($row->agent->latestActiveEffectiveDate->end_date);
            })->values();
        } elseif (sizeof($statuses) == 1 && in_array('0', $statuses)) {
            $rows = $rows->filter(function ($row) {
                return !empty($row->agent->latestActiveEffectiveDate->end_date);
            })->values();
        }

        $agents = $rows->groupBy('agent_id')->values();
        $values = collect([]);
        $totals = [
            'calls' => 0,
            'billable_time' => 0,
        ];
        for ($i = 0; $i <= 31; $i++) {
            $totals[$i] = [
                'calls' => 0,
                'billable_time' => 0,
            ];
        }

        $agents->each(function ($agent) use ($values, &$totals) {
            $value = new \stdClass();
            $value->agent_id = $agent[0]->agent_id;
            $value->agent_name = $agent[0]->agent_name;

            $calls = $agent->sum('calls');
            $totals['calls'] += $calls;
            $billable_time = $agent->sum('billable_time');
            $totals['billable_time'] += $billable_time;
            $value->total = sprintf(self::CELL_FORMAT,
                number_format($billable_time, 2),
                '',
                number_format($calls),
                number_format($billable_time, 2),
            );

            for ($i = 1; $i <= 31; $i++) {
                $date = $agent->where('file_date', $i);

                $calls = $date->pluck('calls')->first();
                $totals[$i]['calls'] += $calls;
                $billable_time = $date->pluck('billable_time')->first();
                $totals[$i]['billable_time'] += $billable_time;
                $value->{$i} = sprintf(self::CELL_FORMAT,
                    number_format($billable_time, 2),
                    $billable_time > 7 ? 'high' : ($billable_time > 0 ? 'medium' : 'low'),
                    number_format($calls),
                    number_format($billable_time, 2),
                );
            }

            $values->push($value);
        });

        $totalValues = [
            'agent_name' => 'TOTALS',
            'total' => sprintf(self::CELL_FORMAT,
                '',
                '',
                number_format($totals['calls']),
                number_format($totals['billable_time'], 2)
            ),
        ];

        for ($i = 1; $i <= 31; $i++) {
            $calls = $totals[$i]['calls'];
            $billable_time = $totals[$i]['billable_time'];
            if ($calls > 0 || $billable_time > 0) {
                $totalValues[$i] = sprintf(self::CELL_FORMAT,
                    '',
                    '',
                    number_format($calls),
                    number_format($billable_time, 2),
                );
            } else {
                $totalValues[$i] = '';
            }
        }

        $columns = [
            ["label" => "Agent Name", "field" => "agent_name", "fixed" => true],
            ["label" => "Total", "field" => "total"],
        ];

        $dates = new Collection(new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate));
        $dates->each(function ($date) use (&$columns) {
            $columns[] = [
                "label" => $date->format('j'), "field" => $date->format('j'),
            ];
        });

        return [
            'rows' => $values,
            'columns' => $columns,
            'totals' => [
                (object) $totalValues,
            ],
        ];
    }
}
