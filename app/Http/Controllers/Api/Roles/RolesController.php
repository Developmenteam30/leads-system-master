<?php

namespace App\Http\Controllers\Api\Roles;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAccessRole;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RolesController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-ROLES:LIST');

        $items = DialerAccessRole::query()
            ->orderBy('name')
            ->get()
            ->append([
                'accessAreasList',
                'notificationTypesList',
            ]);

        $allow_list = [
            'id',
            'abbreviation',
            'name',
            'accessAreasList',
            'notificationTypesList',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Role Name', 'field' => 'name',],
                ['label' => 'Abbreviation', 'field' => 'abbreviation',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Access Roles.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerAccessRole::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Role not found', 400);
            }
        } else {
            $item = new DialerAccessRole();
        }

        $validator = Validator::make($request->all(), [
            'abbreviation' => [
                'bail',
                'nullable',
                'string',
                'max:5',
                !empty($item) ? Rule::unique('dialer_access_roles')->ignore($item) : Rule::unique('dialer_access_roles'),
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_access_roles')->ignore($item) : Rule::unique('dialer_access_roles'),
            ],
            'accessAreasList' => 'bail|nullable|array',
            'notificationTypesList' => 'bail|nullable|array',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->abbreviation = strtoupper($request->input('abbreviation'));
            $item->name = $request->input('name');
            $item->save();

            $item->accessAreas()->sync(collect($request->filled('accessAreasList') ? $request->input('accessAreasList') : []));
            $item->notificationTypes()->sync(collect($request->filled('notificationTypesList') ? $request->input('notificationTypesList') : []));
        });

        return response([]);
    }
}
