<?php

namespace App\Http\Controllers\Api\Writeups;

use App\Helpers\ActionButtonHelper;
use App\Helpers\DataTableFields;
use App\Jobs\SendWriteupNotificationJob;
use App\Jobs\SendWriteupNotificationAgentJob;
use App\Models\AuditLog;
use App\Models\DialerAgentType;
use App\Models\DialerAgentWriteup;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageController extends BaseController
{
    /**
     * Load agent or employee write-ups
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
            'agent_type' => 'bail|string|nullable',
            'actions' => 'bail|nullable|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $agent_type_id = null;
        if (empty($agent_id)) {
            switch ($request->input('agent_type')) {
                case 'agents':
                    $agent_type_id = DialerAgentType::AGENT;
                    break;

                case 'employees':
                    $agent_type_id = DialerAgentType::VISIBLE_EMPLOYEE;
                    break;

                default:
                    return ErrorResponse::json('Invalid agent type', 400);
            }
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'DIALER-WRITEUP:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'company_ids' => $request->input('company_ids'),
            'team_id' => $request->input('team_id'),
            'manager_agent_id' => $request->input('manager_agent_id'),
            'agent_id' => $agent_id,
            'agent_type_id' => $agent_type_id,
        ]);

        $items = DialerAgentWriteup::query()
            ->leftJoin('dialer_agents', 'dialer_agents.id', 'dialer_agent_writeups.agent_id')
            ->leftJoin('dialer_agent_writeup_reasons', 'dialer_agent_writeup_reasons.id', 'dialer_agent_writeups.reason_id')
            ->leftJoin('dialer_agent_writeup_levels', 'dialer_agent_writeup_levels.id', 'dialer_agent_writeups.writeup_level_id')
            ->leftJoin('dialer_agents AS reporter', 'reporter.id', 'dialer_agent_writeups.reporter_agent_id')
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->leftJoin('dialer_agents AS manager', 'manager.id', 'dialer_teams.manager_agent_id')
            ->select([
                'dialer_agent_writeups.id',
                'dialer_agent_writeups.date',
                'dialer_agent_writeups.agent_id',
                'dialer_agent_writeups.reason_id',
                'dialer_agent_writeups.writeup_level_id',
                'dialer_agent_writeups.reporter_agent_id',
                'dialer_agent_writeups.notes',
                'dialer_agents.agent_name',
                'dialer_agent_writeup_reasons.reason',
                DB::raw('dialer_agent_writeup_levels.name AS writeup_level_name'),
                DB::raw('reporter.agent_name AS reporter_name'),
                DB::raw('dialer_teams.name AS team_name'),
                DB::raw('manager.agent_name AS manager_name'),
            ])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('dialer_agent_writeups.date', [
                    $startDate,
                    $endDate,
                ]);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$request->input('search').'%');
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
                return $query->where('dialer_agent_writeups.agent_id', $agent_id);
            })
            ->orderBy('dialer_agent_writeups.date', 'DESC')
            ->with([
                'agent',
            ])
            ->get();

        if (!empty($agent_type_id)) {
            $items = $items->filter(function ($item) use ($request, $agent_type_id) {
                return !empty($item->agent->mostRecentEffectiveDate) && $agent_type_id == $item->agent->mostRecentEffectiveDate->agent_type_id;
            })->values();
        }

        if ($request->boolean('actions')) {
            $items->map(function ($item) use ($request) {
                $item->actions = ActionButtonHelper::view($item);
                if ($request->user()->hasAccessToArea("ACCESS_AREA_WRITEUPS_EDIT") || $item->reporter_agent_id === $request->user()->id) {
                    $item->actions .= ActionButtonHelper::edit($item);
                }
                if ($request->user()->hasAccessToArea("ACCESS_AREA_WRITEUPS_EDIT")) {
                    $item->actions .= ActionButtonHelper::delete($item);
                }

                return $item;
            });
        }

        $allow_list = array_merge([
            'id',
            'date',
            'agent_id',
            'reason_id',
            'reason',
            'notes',
            'reporter_name',
            'team_name',
            'manager_name',
            'writeup_level_id',
            'writeup_level_name',
        ], empty($agent_id) ? ['agent_name'] : [],
            $request->boolean('actions') ? ['actions'] : []
        );

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Date', 'field' => 'date',],
                ['label' => 'Agent Name', 'field' => 'agent_name',],
                ['label' => 'Level', 'field' => 'writeup_level_name',],
                ['label' => 'Reason', 'field' => 'reason',],
                ['label' => 'Team Name', 'field' => 'team_name',],
                ['label' => 'Manager Name', 'field' => 'manager_name',],
                ['label' => 'Reporter Name', 'field' => 'reporter_name',],
                ['label' => 'Actions', 'field' => 'actions',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Write-Ups {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }

    /**
     * Load team writeups
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function team(Request $request, $team_id = null)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|nullable|date',
            'end_date' => 'bail|nullable|date',
            'search' => 'bail|string|nullable',
            'company_id' => 'bail|nullable|exists:companies,idCompany',
            'team_id' => 'bail|nullable|exists:dialer_teams,id',
            'manager_agent_id' => 'bail|nullable|exists:dialer_agents,id',
            'actions' => 'bail|nullable|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'DIALER-WRITEUP:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'company_id' => $request->input('company_id'),
            'team_id' => $request->input('team_id'),
            'manager_agent_id' => $request->input('manager_agent_id'),
        ]);

        $items = DialerAgentWriteup::query()
            ->leftJoin('dialer_agents', 'dialer_agents.id', 'dialer_agent_writeups.agent_id')
            ->leftJoin('dialer_agent_writeup_reasons', 'dialer_agent_writeup_reasons.id', 'dialer_agent_writeups.reason_id')
            ->leftJoin('dialer_agent_writeup_levels', 'dialer_agent_writeup_levels.id', 'dialer_agent_writeups.writeup_level_id')
            ->leftJoin('dialer_agents AS reporter', 'reporter.id', 'dialer_agent_writeups.reporter_agent_id')
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->leftJoin('dialer_agents AS manager', 'manager.id', 'dialer_teams.manager_agent_id')
            ->select([
                'dialer_agent_writeups.id',
                'dialer_agent_writeups.date',
                'dialer_agent_writeups.agent_id',
                'dialer_agent_writeups.reason_id',
                'dialer_agent_writeups.writeup_level_id',
                'dialer_agent_writeups.reporter_agent_id',
                'dialer_agent_writeups.notes',
                'dialer_agents.agent_name',
                'dialer_agent_writeup_reasons.reason',
                DB::raw('dialer_agent_writeup_levels.name AS writeup_level_name'),
                DB::raw('reporter.agent_name AS reporter_name'),
                DB::raw('dialer_teams.name AS team_name'),
                DB::raw('manager.agent_name AS manager_name'),
            ])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('dialer_agent_writeups.date', [
                    $startDate,
                    $endDate,
                ]);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->filled('company_id'), function ($query) use ($request) {
                return $query->where('dialer_agents.company_id', $request->input('company_id'));
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                return $query->where('dialer_teams.id', $request->input('team_id'));
            })
            ->when($request->filled('manager_agent_id'), function ($query) use ($request) {
                return $query->where('dialer_teams.manager_agent_id', $request->input('manager_agent_id'));
            })
            ->orderBy('dialer_agent_writeups.date', 'DESC')
            ->get();

        if ($request->boolean('actions')) {
            $items->map(function ($item) use ($request) {
                $item->actions = ActionButtonHelper::view($item);
                if ($request->user()->hasAccessToArea("ACCESS_AREA_WRITEUPS_EDIT") || $item->reporter_agent_id === $request->user()->id) {
                    $item->actions .= ActionButtonHelper::edit($item);
                }
                if ($request->user()->hasAccessToArea("ACCESS_AREA_WRITEUPS_EDIT")) {
                    $item->actions .= ActionButtonHelper::delete($item);
                }

                return $item;
            });
        }

        $allow_list = array_merge([
            'id',
            'date',
            'agent_id',
            'agent_name',
            'reason_id',
            'reason',
            'notes',
            'reporter_name',
            'team_name',
            'manager_name',
            'writeup_level_id',
            'writeup_level_name',
        ], $request->boolean('actions') ? ['actions'] : []
        );

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Date', 'field' => 'date',],
                ['label' => 'Agent Name', 'field' => 'agent_name',],
                ['label' => 'Level', 'field' => 'writeup_level_name',],
                ['label' => 'Reason', 'field' => 'reason',],
                ['label' => 'Team Name', 'field' => 'team_name',],
                ['label' => 'Manager Name', 'field' => 'manager_name',],
                ['label' => 'Reporter Name', 'field' => 'reporter_name',],
                ['label' => 'Actions', 'field' => 'actions',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Write-Ups {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }

    /**
     * Add or edit a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'bail|required|exists:dialer_agents,id',
            'date' => 'bail|required|date',
            'writeup_level_id' => 'bail|required|exists:dialer_agent_writeup_levels,id',
            'reason_id' => 'bail|required|exists:dialer_agent_writeup_reasons,id',
            'notes' => 'bail|required|string|max:65535',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if (!empty($item_id)) {
            $item = DialerAgentWriteup::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Writeup not found', 400);
            }

            if (!$request->user()->hasAccessToArea("ACCESS_AREA_WRITEUPS_EDIT") && $item->reporter_agent_id !== $request->user()->id) {
                return ErrorResponse::json('You do not have access to edit this write-up.', 400);
            }
        } else {
            $item = new DialerAgentWriteup();
            $item->reporter_agent_id = $request->user()->id;
        }

        DB::transaction(function () use ($item, $request) {
            $item->date = $request->input('date');
            $item->agent_id = $request->input('agent_id');
            $item->writeup_level_id = $request->input('writeup_level_id');
            $item->reason_id = $request->input('reason_id');
            $item->notes = $request->input('notes');
            $item->save();
        });

        if ($item->wasRecentlyCreated) {
            SendWriteupNotificationJob::dispatch($item);
            if (!empty($item->agent->email)) {
                SendWriteupNotificationAgentJob::dispatch($item);
            }
        }

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
            'id' => 'required|bail|exists:dialer_agent_writeups,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentWriteup::withTrashed()->find($item_id);
        $item->delete();

        return response([]);
    }
}
