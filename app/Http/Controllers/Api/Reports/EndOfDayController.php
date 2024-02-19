<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Jobs\EoDReportEmailJob;
use App\Models\AuditLog;
use App\Models\DialerEodReport;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EndOfDayController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|nullable|date',
            'end_date' => 'bail|nullable|date',
            'manager_agent_id' => 'bail|nullable|exists:dialer_agents,id',
            'team_id' => 'bail|nullable|exists:dialer_teams,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        AuditLog::createFromRequest($request, 'END-OF-DAY-REPORTS:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'manager_agent_id' => $request->input('manager_agent_id'),
            'team_id' => $request->input('team_id'),
        ]);

        $items = DialerEodReport::query()
            ->with(['managerAgent', 'team'])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween(DB::raw("CONVERT_TZ(created_at,'UTC','".config('settings.timezone.local')."')"), [
                    $startDate,
                    $endDate,
                ]);
            })
            ->when($request->filled('manager_agent_id'), function ($query) use ($request) {
                $query->where('manager_agent_id', $request->input('manager_agent_id'));
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                $query->where('team_id', $request->input('team_id'));
            })
            ->orderBy('created_at')
            ->get();

        $allow_list = [
            'id',
            'reportDate',
            'manager_agent_name',
            'team_name',
            'team_count',
            'head_count',
            'attendance_notes',
            'early_leave',
            'day_prior_auto_fail',
            'day_prior_calls_under_89pct',
            'completed_evaluations',
            'agents_coached',
            'agents_on_pip',
            'notes',
        ];

        $items->each(function ($item) {
            $item->manager_agent_name = $item->managerAgent->agent_name ?? '';
            $item->team_name = $item->team->name ?? '';

            return $item;
        });

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Date', 'field' => 'reportDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Manager Agent', 'field' => 'manager_agent_name'],
                ['label' => 'Team', 'field' => 'team_name'],
                ['label' => 'Team Count', 'field' => 'team_count', 'fixed' => true],
                ['label' => 'Head Count', 'field' => 'head_count', 'fixed' => true],
                ['label' => 'Attendance Notes', 'field' => 'attendance_notes'],
                ['label' => 'Early Leave', 'field' => 'early_leave', 'fixed' => true],
                ['label' => 'Day Prior Auto Fail', 'field' => 'day_prior_auto_fail', 'fixed' => true],
                ['label' => 'Day Prior Calls Under 89%', 'field' => 'day_prior_calls_under_89pct', 'fixed' => true],
                ['label' => 'Completed Evaluations', 'field' => 'completed_evaluations', 'fixed' => true],
                ['label' => 'Agents Coached', 'field' => 'agents_couched', 'fixed' => true],
                ['label' => 'Agents On PIP', 'field' => 'agents_on_pip', 'fixed' => true],
                ['label' => 'Notes', 'field' => 'notes'],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "End of Day Reports.xlsx");
    }

    /**
     * Add a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerEodReport::find($item_id);
            if (!$item) {
                return ErrorResponse::json('EOD Report Not Found', 400);
            }
        } else {
            $item = new DialerEodReport();
        }

        $validator = Validator::make($request->all(), [
            'manager_agent_id' => 'required|exists:dialer_agents,id',
            'team_id' => 'required|exists:dialer_teams,id',
            'team_count' => 'required|integer|min:0',
            'head_count' => 'required|integer|min:0',
            'attendance_notes' => 'required|string',
            'early_leave' => 'required|string',
            'day_prior_auto_fail' => 'required|integer|min:0',
            'day_prior_calls_under_89pct' => 'required|integer|min:0',
            'completed_evaluations' => 'required|integer|min:0',
            'agents_coached' => 'required|integer|min:0',
            'agents_on_pip' => 'required|integer|min:0',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->manager_agent_id = $request->input('manager_agent_id');
            $item->team_id = $request->input('team_id');
            $item->team_count = $request->input('team_count');
            $item->head_count = $request->input('head_count');
            $item->attendance_notes = $request->input('attendance_notes');
            $item->early_leave = $request->input('early_leave');
            $item->day_prior_auto_fail = $request->input('day_prior_auto_fail');
            $item->completed_evaluations = $request->input('completed_evaluations');
            $item->agents_coached = $request->input('agents_coached');
            $item->agents_on_pip = $request->input('agents_on_pip');
            $item->notes = $request->input('notes');
            $item->save();
        });

        EoDReportEmailJob::dispatch(report: $item);

        return response([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(dialer_eod_reports $dialer_eod_reports)
    {
        //
    }
}
