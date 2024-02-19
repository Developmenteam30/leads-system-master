<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAgentType;
use App\Models\DialerDispositionLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DispositionsController extends BaseController
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
        $company = null;

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS")) {
            if (empty($request->user()->company_id)) {
                return ErrorResponse::json('No company ID is set.', 401);
            } else {
                $company = Company::find($request->user()->company_id);
                if (!$company) {
                    return ErrorResponse::json('Company is not found.', 401);
                }
                if (Company::DIALER_REPORT_TYPE_BILLABLE !== $company->dialer_report_type) {
                    $request->merge(['company_id' => $request->user()->company_id]);
                }
            }
        }

        AuditLog::createFromRequest($request, 'REPORT:DIALER-DISPOSITIONS', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'company_id' => $request->input('company_id'),
        ]);

        $columns = DialerDispositionLog::query()
            ->join('dialer_statuses', 'dialer_statuses.id', 'dialer_disposition_logs.status_id')
            ->select([
                DB::raw("CONCAT('status_',dialer_statuses.id) AS field"),
                DB::raw('dialer_statuses.status_name AS label'),
                DB::raw("'integer' AS displayFormat"),
                DB::raw("2 AS colSpan"),
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->groupBy([
                'dialer_statuses.id',
            ])
            ->orderBy('dialer_statuses.status_name')
            ->get();

        $columns->prepend([
            'label' => 'Total',
            'field' => 'agent_total',
            'displayFormat' => 'text',
            'colSpan' => 1,
        ]);

        $columns->prepend([
            'label' => 'Agent Name',
            'field' => 'agent_name',
            'fixed' => true,
            'displayFormat' => 'text',
            'colSpan' => 1,
        ]);

        // Define the base query
        $baseQuery = DialerDispositionLog::query()
            ->join('dialer_statuses', 'dialer_statuses.id', 'dialer_disposition_logs.status_id')
            ->whereBetween('file_date', [$startDate, $endDate])
            ->groupBy('dialer_statuses.id')
            ->orderBy('dialer_statuses.status_name');

        // Execute the first query
        $result1 = (clone $baseQuery)
            ->select([
                DB::raw("CONCAT('status_',dialer_statuses.id) AS field"),
                DB::raw('"count" AS label'),
                DB::raw("'integer' AS displayFormat"),
            ])
            ->get();

        // Execute the second query
        $result2 = (clone $baseQuery)
            ->select([
                DB::raw("CONCAT('pct_status_',dialer_statuses.id) AS field"),
                DB::raw("'pct' AS label"),
                DB::raw("'percentage' AS displayFormat"),
            ])
            ->get();

        // Interlace the results
        $subColumns = collect();
        foreach ($result1 as $index => $value) {
            $subColumns->push($value);

            if (isset($result2[$index])) { // assuming that both collections have the same size
                $subColumns->push($result2[$index]);
            }
        }

        $subColumns->prepend([
            'label' => '',
            'field' => 'agent_total',
            'displayFormat' => 'integer',
        ]);

        $subColumns->prepend([
            'label' => '',
            'field' => 'agent_name',
            'fixed' => true,
            'displayFormat' => 'text',
        ]);

        $rows = DialerDispositionLog::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_disposition_logs.agent_id')
            ->leftJoin('dialer_agent_companies', 'dialer_agents.id', 'dialer_agent_companies.agent_id')
            ->joinEffectiveDates()
            ->select([
                'dialer_disposition_logs.agent_id',
                'dialer_agents.agent_name',
            ])
            ->whereBetween('file_date', [
                $startDate,
                $endDate,
            ])
            ->where('dialer_agents.agent_name', 'NOT LIKE', 'System%')
            ->when($request->filled('company_id'), function ($query) use ($request) {
                $query->where('dialer_agents.company_id', $request->input('company_id'));
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
            ->groupBy([
                'dialer_disposition_logs.agent_id',
            ])
            ->orderBy('dialer_agents.agent_name')
            ->get();

        $rows->each(function ($agent) use ($startDate, $endDate) {
            $counts = DialerDispositionLog::query()
                ->select([
                    'status_id',
                    DB::raw("SUM(total) AS total"),
                ])
                ->whereBetween('file_date', [
                    $startDate,
                    $endDate,
                ])
                ->where('agent_id', $agent->agent_id)
                ->groupBy([
                    'status_id',
                ])
                ->get();

            $agent->agent_total = $counts->sum('total');

            $counts->each(function ($count) use ($agent) {
                $agent->{"status_{$count->status_id}"} = $count->total;
                $agent->{"pct_status_{$count->status_id}"} = round(($count->total / $agent->agent_total) * 100, 1);
            });

        });

        $totals = [];
        $subColumns->pluck('field')->each(function ($column) use ($rows, &$totals) {
            if ('agent_name' === $column) {
                $totals[$column] = 'TOTALS';
            } elseif (str_contains($column, "pct_status_")) {
                $totals[$column] = round($rows->avg($column), 1);
            } else {
                $totals[$column] = $rows->sum($column);
            }
        });

        // NOTE: if using subcolumns then we need to flip the variables of columns / subColumns
        $datatable = [
            'columns' => $subColumns,
            'subColumns' => $columns,
            'rows' => $rows,
            'totals' => [$totals],
        ];

        return DataTableFields::displayOrExport($datatable, collect($datatable['columns'])->pluck('field')->toArray(), $request,
            "Dispositions Report {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");

    }
}
