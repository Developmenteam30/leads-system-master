<?php

namespace App\Http\Controllers\Api\Teams;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerTeam;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ManageController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'bail|string|nullable',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        AuditLog::createFromRequest($request, 'DIALER-TEAM:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerTeam::query()
            ->select([
                'id',
                'name',
                'manager_agent_id',
                'deleted_at',
            ])
            ->with([
                'manager',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('name')
            ->get()
            ->append([
                'team_lead_agent_ids',
                'team_lead_agent_names_string',
            ]);

        $items->map(function ($item) {
            $item->manager_agent_name = $item->manager->agent_name ?? '';

            return $item;
        });

        $allow_list = array_merge([
            'id',
            'name',
            'isActive',
            'isArchived',
            'manager_agent_name',
            'manager_agent_id',
            'team_lead_agent_ids',
            'team_lead_agent_names_string',
        ]);

        $datatable = [
            'columns' => [
                //['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Team Name', 'field' => 'name', 'fixed' => true,],
                ['label' => 'Manager Name', 'field' => 'manager_agent_name',],
                ['label' => 'Team Leads', 'field' => 'team_lead_agent_names_string',],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Teams List.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerTeam::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Team not found', 400);
            }
        } else {
            $item = new DialerTeam();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_teams')->ignore($item) : Rule::unique('dialer_teams'),
            ],
            'manager_agent_id' => 'bail|nullable|exists:dialer_agents,id',
            'team_lead_agent_ids' => 'bail|nullable|string',
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->name = $request->input('name');
            $item->manager_agent_id = $request->input('manager_agent_id');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    $item->delete();
                } else {
                    $item->restore();
                }
            }
            $item->save();

            $item->leads()->sync(collect($request->filled('team_lead_agent_ids') ? explode(',', $request->input('team_lead_agent_ids')) : []));
        });

        return response([]);
    }

    /**
     * Load team members
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function members(Request $request, $teamId)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'teamId' => 'bail|required|exists:dialer_teams,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $items = DialerAgent::query()
            ->select([
                'id',
                'agent_name',
            ])
            ->where('team_id', $teamId)
            ->orderBy('agent_name')
            ->get();

        $allow_list = array_merge([
            'id',
            'agent_name',
        ]);

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::getByAllowList($datatable, $allow_list);
    }
}
