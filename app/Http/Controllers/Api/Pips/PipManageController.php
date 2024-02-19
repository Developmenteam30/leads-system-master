<?php

namespace App\Http\Controllers\Api\Pips;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgentPip;
use App\Models\DialerAgentTermination;
use App\Models\DialerPipResolution;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PipManageController extends BaseController
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
            'search' => 'bail|string|nullable',
            'company_ids' => 'bail|string|nullable',
            'team_ids' => 'bail|string|nullable',
            'manager_agent_ids' => 'bail|string|nullable',
            'actions' => 'bail|nullable|boolean',
            'agent_id' => 'bail|string|nullable',
            'include_resolved' => 'bail|string',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'DIALER-PIPS:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'company_ids' => $request->input('company_ids'),
            'team_ids' => $request->input('team_ids'),
            'manager_agent_ids' => $request->input('manager_agent_ids'),
            'agent_id' => $request->input('agent_id'),
            'include_resolved' => $request->input('include_resolved'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerAgentPip::query()
            ->leftJoin('dialer_agents', 'dialer_agents.id', 'dialer_agent_pips.agent_id')
            ->leftJoin('dialer_agents AS reporter', 'reporter.id', 'dialer_agent_pips.reporter_agent_id')
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->leftJoin('dialer_agents AS manager', 'manager.id', 'dialer_teams.manager_agent_id')
            ->leftJoin('dialer_pip_resolutions AS resolution', 'dialer_agent_pips.resolution_id', 'resolution.id')
            ->select([
                'dialer_agent_pips.id',
                'dialer_agent_pips.start_date',
                'dialer_agent_pips.agent_id',
                'dialer_agent_pips.reporter_agent_id',
                'dialer_agent_pips.deleted_at',
                'dialer_agents.agent_name',
                DB::raw('reporter.agent_name AS reporter_name'),
                DB::raw('resolution.resolution AS resolution_name'),
                DB::raw('dialer_teams.name AS team_name'),
                DB::raw('manager.agent_name AS manager_name'),
            ])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('dialer_agent_pips.start_date', [
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
            ->when($request->filled('team_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_teams.id', explode(',', $request->input('team_ids')));
            })
            ->when($request->filled('manager_agent_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_teams.manager_agent_id', explode(',', $request->input('manager_agent_ids')));
            })
            ->when($request->filled('agent_id'), function ($query) use ($request) {
                return $query->where('dialer_agent_pips.agent_id', $request->input('agent_id'));
            })
            ->when(!$request->boolean('include_resolved'), function ($query) use ($request) {
                $query->whereNull('resolution_id');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('dialer_agent_pips.start_date', 'DESC')
            ->get()
            ->append([
                'reason_ids',
                'reasons_string',
            ]);;

        if ($request->boolean('actions')) {
            $items->map(function ($item) use ($request) {
                $item->actions .= '<button title="View" class="view-btn btn btn-outline-primary btn-floating btn-sm" data-mdb-number="'.$item->id.'"><i class="fa fa-eye"></i></button>';
                if ($request->user()->hasAccessToArea("ACCESS_AREA_PIPS_EDIT") || $item->reporter_agent_id === $request->user()->id) {
                    $item->actions .= '<button title="Edit" class="edit-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->id.'"><i class="fa fa-edit"></i></button>';
                }
                if (!$request->boolean('embedded')) {
                    $item->actions .= '<button title="History" class="history-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->agent_id.'"><i class="fa fa-history"></i></button>';
                }
                if ($request->user()->hasAccessToArea("ACCESS_AREA_PIPS_EDIT")) {
                    if ($item->trashed()) {
                        $item->actions .= '<button title="Undelete" class="restore-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->id.'"><i class="fa fa-trash-restore"></i></button>';
                    } else {
                        $item->actions .= '<button title="Delete" class="delete-btn btn btn-outline-danger btn-floating btn-sm ms-2 text-danger" data-mdb-number="'.$item->id.'"><i class="fa fa-trash"></i></button>';
                    }
                }

                return $item;
            });
        }

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Start Date', 'field' => 'start_date', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Agent Name', 'field' => 'agent_name', 'show' => $request->isNotFilled('agent_id'),],
                ['label' => 'Team Name', 'field' => 'team_name',],
                ['label' => 'Manager Name', 'field' => 'manager_name',],
                ['label' => 'Reporter Name', 'field' => 'reporter_name',],
                ['label' => 'Resolution', 'field' => 'resolution_name',],
                ['label' => 'Actions', 'field' => 'actions', 'show' => !$request->filled('export'),],
                ['label' => 'Reasons', 'field' => 'reasons_string', 'show' => $request->filled('export'),],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, false, $request, "PIPs {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
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
            'start_date' => 'bail|required|date',
            'reason_ids' => 'bail|required|string',
            'resolution_id' => 'bail|nullable|exists:dialer_pip_resolutions,id',
            /*
            'termination_date' => [
                'bail',
                'nullable',
                Rule::requiredIf(fn() => $request->input('resolution_id') === DialerPipResolution::FAIL),
                'date',
            ],
            */
            'termination_reason_id' => [
                'bail',
                'nullable',
                Rule::requiredIf(fn() => $request->input('resolution_id') === DialerPipResolution::FAIL),
                'exists:dialer_agent_termination_reasons,id',
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if (!empty($item_id)) {
            $item = DialerAgentPip::find($item_id);
            if (!$item) {
                return ErrorResponse::json('PIP not found', 400);
            }

            if (!$request->user()->hasAccessToArea("ACCESS_AREA_PIPS_EDIT") && $item->reporter_agent_id !== $request->user()->id) {
                return ErrorResponse::json('You do not have access to edit this PIP.', 400);
            }
        } else {
            $item = new DialerAgentPip();
            $item->reporter_agent_id = $request->user()->id;
        }

        DB::transaction(function () use ($item, $request) {
            $item->start_date = $request->input('start_date');
            $item->agent_id = $request->input('agent_id');
            $item->resolution_id = $request->input('resolution_id');

            if (empty($item->end_date)) {
                switch ($request->input('resolution_id')) {
                    case DialerPipResolution::FAIL:
                        $item->markAsFailed($request->user()->id, $request->input('termination_reason_id'));
                        break;

                    case DialerPipResolution::EXTEND:
                        $item->markAsExtended($request->user()->id);
                        break;
                }
            }

            $item->save();

            $item->reasons()->sync(collect($request->filled('reason_ids') ? explode(',', $request->input('reason_ids')) : []));
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
            'id' => 'required|bail|exists:dialer_agent_pips,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentPip::withTrashed()->find($item_id);
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
            'id' => 'required|bail|exists:dialer_agent_pips,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentPip::withTrashed()->find($item_id);
        $item->restore();

        return response([]);
    }
}
