<?php

namespace App\Http\Controllers\Api\Evaluations;

use App\Helpers\DataTableFields;
use App\Jobs\SendWriteupNotificationJob;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentEvaluation;
use App\Models\DialerAgentType;
use App\Models\DialerAgentWriteup;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluationsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|nullable|date',
            'end_date' => 'bail|nullable|date',
            'search' => 'bail|string|nullable',
            'company_ids' => 'bail|nullable',
            'team_id' => 'bail|nullable|exists:dialer_teams,id',
            'manager_agent_id' => 'bail|nullable|exists:dialer_agents,id',
            'status' => 'bail|nullable|string',
            'actions' => 'bail|nullable|boolean',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'DIALER-EVALUATION:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'company_ids' => $request->input('company_ids'),
            'team_id' => $request->input('team_id'),
            'manager_agent_id' => $request->input('manager_agent_id'),
            'agent_id' => $agent_id,
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerAgent::query()
            ->join('dialer_agent_effective_dates', 'dialer_agents.id', 'dialer_agent_effective_dates.agent_id')
            ->leftJoin('dialer_agent_evaluations', function (JoinClause $join) use ($request) {
                $join->on('dialer_agents.id', 'dialer_agent_evaluations.agent_id')
                    ->when(!$request->boolean('include_archived'), function (JoinClause $join) {
                        return $join->whereNull('dialer_agent_evaluations.deleted_at');
                    });
            })
            ->leftJoin('dialer_agents AS reporter', 'reporter.id', 'dialer_agent_evaluations.reporter_agent_id')
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->leftJoin('dialer_agents AS manager', 'manager.id', 'dialer_teams.manager_agent_id')
            ->select([
                DB::raw('dialer_agents.id AS agent_id'),
                DB::raw("IFNULL(dialer_agent_evaluations.id,'-') AS evaluation_id"),
                'dialer_agent_evaluations.start_date',
                'dialer_agent_evaluations.end_date',
                'dialer_agent_evaluations.writeup_id',
                'dialer_agent_evaluations.created_at',
                'dialer_agent_evaluations.deleted_at',
                'dialer_agents.agent_name',
                DB::raw('reporter.agent_name AS reporter_name'),
                DB::raw('dialer_teams.name AS team_name'),
                DB::raw('manager.agent_name AS manager_name'),
            ])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate, $request) {
                $query->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('dialer_agent_evaluations.created_at', [
                        $startDate,
                        $endDate,
                    ])
                        ->orWhereNull('dialer_agent_evaluations.id');
                })
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('dialer_agent_effective_dates.start_date', '<=', $endDate)
                            ->where(function ($query) use ($startDate) {
                                $query->whereNull('dialer_agent_effective_dates.end_date')
                                    ->orWhere('dialer_agent_effective_dates.end_date', '>=', $startDate);
                            });
                    });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ('completed' === $request->input('status')) {
                    return $query->whereNotNull('dialer_agent_evaluations.id');
                } else {
                    return $query->whereNull('dialer_agent_evaluations.id');
                }
            })
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                return $query->where('dialer_teams.id', $request->input('team_id'));
            })
            ->when($request->filled('manager_agent_id'), function ($query) use ($request) {
                return $query->where('dialer_teams.manager_agent_id', $request->input('manager_agent_id'));
            })
            ->when(!empty($agent_id), function ($query) use ($agent_id) {
                return $query->where('dialer_agent_evaluations.agent_id', $agent_id);
            })
            ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
            ->orderBy('dialer_agents.agent_name')
            ->get();

        $allow_list = array_merge([
            'agent_id',
            'evaluation_id',
            'id',
            'created_at',
            'start_date',
            'end_date',
            'writeup_id',
            'reporter_name',
            'team_name',
            'manager_name',
            'actions',
        ], empty($agent_id) ? ['agent_name'] : []
        );

        $cnt = 0;
        if ($request->boolean('actions')) {
            $items->map(function ($item) use ($request, &$cnt) {
                // This is needed for AbstractList, which searches by "id", but we can't use
                // the database id here because it doesn't always exist.
                $item->id = ++$cnt;
                if ($request->user()->hasAccessToArea("ACCESS_AREA_EVALUATIONS_EDIT")) {
                    $item->actions .= '<button title="View" class="view-btn btn btn-outline-primary btn-floating btn-sm" data-mdb-number="'.$cnt.'"><i class="fa fa-eye"></i></button>';
                    if ($item->evaluation_id && $item->evaluation_id != '-'):
                        if ($item->deleted_at !== null) {
                            $item->actions .= '<button title="Undelete" class="restore-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->evaluation_id.'"><i class="fa fa-trash-restore"></i></button>';
                        } else {
                            $item->actions .= '<button title="Delete" class="delete-btn btn btn-outline-danger btn-floating btn-sm ms-2 text-danger" data-mdb-number="'.$item->evaluation_id.'"><i class="fa fa-trash"></i></button>';
                        }
                    endif;
                }

                return $item;
            });
        }
        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'evaluation_id', 'fixed' => true],
                ['label' => 'Agent Name', 'field' => 'agent_name',],
                ['label' => 'Eval Date', 'field' => 'created_at', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                //['label' => 'Team Name', 'field' => 'team_name',],
                //['label' => 'Manager Name', 'field' => 'manager_name',],
                ['label' => 'Evaluator Name', 'field' => 'reporter_name',],
                ['label' => 'Week Start', 'field' => 'start_date',],
                ['label' => 'Week End', 'field' => 'end_date',],
                ['label' => 'Write-Up', 'field' => 'writeup_id', 'displayFormat' => 'vote_yes',],
                ['label' => 'Actions', 'field' => 'actions', 'show' => !$request->filled('export'),],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Evaluations {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public
    function update(
        Request $request,
        $item_id = null
    ) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|required|date',
            'end_date' => 'bail|required|date',
            'agent_id' => 'bail|required|exists:dialer_agents,id',
            'notes' => 'bail|nullable|string|max:65535',
            'reason_id' => 'bail|nullable|exists:dialer_agent_writeup_reasons,id',
            'writeup_level_id' => 'bail|nullable|required_with:reason_id|exists:dialer_agent_writeup_levels,id',
            'writeup_notes' => 'bail|nullable|required_with:reason_id|string|max:65535',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if (!empty($item_id)) {
            $item = DialerAgentEvaluation::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Evaluation not found', 400);
            }
        } else {
            $item = new DialerAgentEvaluation();
            $item->reporter_agent_id = $request->user()->id;
        }

        DB::transaction(function () use ($item, $request) {
            if ($request->filled('reason_id')) {
                $writeup = new DialerAgentWriteup();
                $writeup->reporter_agent_id = $request->user()->id;
                $writeup->date = now(config('settings.timezone.local'));
                $writeup->agent_id = $request->input('agent_id');
                $writeup->reason_id = $request->input('reason_id');
                $writeup->writeup_level_id = $request->input('writeup_level_id');
                $writeup->notes = $request->input('writeup_notes');
                $writeup->save();

                SendWriteupNotificationJob::dispatch($writeup);
            }

            $item->agent_id = $request->input('agent_id');
            $item->start_date = $request->input('start_date');
            $item->end_date = $request->input('end_date');
            $item->writeup_id = $writeup->id ?? null;
            $item->notes = $request->input('notes');
            $item->save();
        });

        return response([]);
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_agent_evaluations,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentEvaluation::withTrashed()->find($item_id);
        $item->delete();

        return response([]);
    }

    /**
     * Restore a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function restore(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_agent_evaluations,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentEvaluation::withTrashed()->find($item_id);
        $item->restore();

        return response([]);
    }
}
