<?php

namespace App\Http\Controllers\Api\Reports;

use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerformanceController extends BaseController
{
    const CELL_FORMAT = '<div class="performance-cell"><span class="performance-transfers">%s</span> <span class="performance-calls">%s</span> <span class="performance-percentage performance-percentage-%s">%.2f%%</span> <span class="performance-wrapup">%s</span></div>';

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

        AuditLog::createFromRequest($request, 'REPORT:PERFORMANCE', [
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
                DB::raw("SUM(dialer_agent_performances.transfers) AS transfers"),
                DB::raw("AVG(dialer_agent_performances.wrapup_time) AS wrapup_time"),
                DB::raw("IF(SUM(dialer_agent_performances.transfers) > 0,ROUND((SUM(dialer_agent_performances.transfers)/SUM(dialer_agent_performances.calls))*100,2),0) AS percentage"),
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
        $dailyAverages = [];
        $totals = [
            'calls' => 0,
            'transfers' => 0,
            'wrapup_time' => 0,
        ];
        for ($i = 0; $i <= 31; $i++) {
            $totals[$i] = [
                'calls' => 0,
                'transfers' => 0,
                'wrapup_time' => 0,
            ];
        }

        for ($i = 1; $i <= 31; $i++) {
            $dailyCalls = $rows->where('file_date', $i)->sum('calls');
            $dailyTransfers = $rows->where('file_date', $i)->sum('transfers');
            $dailyAverages[$i] = round($dailyCalls > 0 ? ($dailyTransfers / $dailyCalls) * 100 : 0, 2);
        }

        $agents->each(function ($agent) use ($dailyAverages, $values, &$totals) {
            $value = new \stdClass();
            $value->agent_id = $agent[0]->agent_id;
            $value->agent_name = $agent[0]->agent_name;

            $transfers = $agent->sum('transfers');
            $totals['transfers'] += $transfers;
            $calls = $agent->sum('calls');
            $totals['calls'] += $calls;
            $wrapup_time = round($agent->average('wrapup_time'));
            $totals['wrapup_time'] += $wrapup_time;
            $percent = round($calls > 0 ? ($transfers / $calls) * 100 : 0, 2);
            $value->total = sprintf(self::CELL_FORMAT,
                number_format($transfers),
                number_format($calls),
                $percent > 1.2 ? 'high' : ($percent >= 0.9 && $percent <= 1.2 ? 'medium' : 'low'),
                $percent,
                Carbon::parse("@".$wrapup_time)->format("H:m:s")
            );

            for ($i = 1; $i <= 31; $i++) {
                $date = $agent->where('file_date', $i);

                $transfers = $date->pluck('transfers')->first();
                $totals[$i]['transfers'] += $transfers;
                $calls = $date->pluck('calls')->first();
                $totals[$i]['calls'] += $calls;
                $wrapup_time = $date->pluck('wrapup_time')->first();
                $totals[$i]['wrapup_time'] += $wrapup_time;
                $percent = round($calls > 0 ? ($transfers / $calls) * 100 : 0, 2);
                if ($calls > 0 || $transfers > 0) {
                    $value->{$i} = sprintf(self::CELL_FORMAT,
                        number_format($transfers),
                        number_format($calls),
                        $percent > ($dailyAverages[$i] * 1.2) ? 'high' : ($percent < ($dailyAverages[$i] * 0.8) ? 'low' : 'medium'),
                        $percent,
                        $wrapup_time > 60 * 60 ? Carbon::parse("@".$wrapup_time)->format("H:m:s") : Carbon::parse("@".$wrapup_time)->format("m:s")
                    );
                } else {
                    $value->{$i} = '';
                }
            }

            $values->push($value);
        });

        $percent = round($totals['calls'] > 0 ? ($totals['transfers'] / $totals['calls']) * 100 : 0, 2);
        $totalValues = [
            'agent_name' => 'TOTALS',
            'total' => sprintf(self::CELL_FORMAT,
                number_format($totals['transfers']),
                number_format($totals['calls']),
                $percent > 1.2 ? 'high' : ($percent >= 0.9 && $percent <= 1.2 ? 'medium' : 'low'),
                $percent,
                Carbon::parse("@".$totals['wrapup_time'])->format("H:m:s")

            ),
        ];

        for ($i = 1; $i <= 31; $i++) {
            $transfers = $totals[$i]['transfers'];
            $calls = $totals[$i]['calls'];
            $wrapup_time = $totals[$i]['wrapup_time'];
            $percent = round($calls > 0 ? ($transfers / $calls) * 100 : 0, 2);
            if ($calls > 0 ||
                $transfers > 0) {
                $totalValues[$i] = sprintf(self::CELL_FORMAT,
                    number_format($transfers),
                    number_format($calls),
                    $percent > 1.2 ? 'high' : ($percent >= 0.9 && $percent <= 1.2 ? 'medium' : 'low'),
                    $percent,
                    $wrapup_time > 60 * 60 ? Carbon::parse("@".$wrapup_time)->format("H:m:s") : Carbon::parse("@".$wrapup_time)->format("m:s")
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

    /**
     * Load details
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|string|exists:dialer_agents,id',
            'date' => 'required|bail|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $agent = DialerAgent::find($request->input('agent_id'));
        $date = Carbon::parse($request->input('date'));
        $monthlyReport = preg_match('/^\d{4}-\d{2}$/', $request->input('date'));

        AuditLog::createFromRequest($request, 'REPORT:PERFORMANCE-DETAILS', [
            'agent_id' => $agent->id,
            'date' => $monthlyReport ? $date->format('Y-m') : $date->format('Y-m-d'),
        ]);

        $rows = DialerLog::query()
            ->select([
                'call_id',
                'first_name',
                'last_name',
                'number_dialed',
                'status_name',
                'talk_time',
                'wrapup_time',
                'bill_time',
                'recordings',
                'time_stamp',
            ])
            ->when($monthlyReport, function ($query) use ($date) {
                $query->whereBetween('time_stamp', [
                    $date->format('Y-m-01 00:00:00'),
                    $date->format('Y-m-t 23:59:59'),
                ]);
            }, function ($query) use ($date) {
                $query->timestampQuery($date->format('Y-m-d'));
            })
            //->whereIn('status', ['ST', 'TFCB', 'STSSDI'])
            ->where('agent_id', $agent->id)
            ->orderBy('time_stamp')
            ->get();

        $rows->each(function ($row) {
            if (!empty($row->recordings)) {
                $row->recordings = sprintf('<a href="%s">Play</a>',
                    $row->recordings
                );
            }

            $row->talk_time = !empty($row->talk_time) ? Carbon::parse("@".$row->talk_time)->format("H:m:s") : '';
            $row->wrapup_time = !empty($row->wrapup_time) ? Carbon::parse("@".$row->wrapup_time)->format("H:m:s") : '';
        });

        return [
            'columns' => [
                ['label' => 'Call ID', 'field' => 'call_id'],
                ['label' => 'Timestamp', 'field' => 'time_stamp'],
                ['label' => 'First Name', 'field' => 'first_name'],
                ['label' => 'Last Name', 'field' => 'last_name'],
                ['label' => 'Number Dialed', 'field' => 'number_dialed'],
                ['label' => 'Status', 'field' => 'status_name'],
                ['label' => 'Talk Time', 'field' => 'talk_time'],
                ['label' => 'Wrapup Time', 'field' => 'wrapup_time'],
                ['label' => 'Bill Time', 'field' => 'bill_time'],
                ['label' => 'Recording', 'field' => 'recordings'],
            ],
            'rows' => $rows,
        ];
    }
}
