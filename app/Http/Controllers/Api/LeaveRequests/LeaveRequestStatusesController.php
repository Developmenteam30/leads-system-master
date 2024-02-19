<?php

namespace App\Http\Controllers\Api\LeaveRequests;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerLeaveRequestStatus;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeaveRequestStatusesController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $agent_id = null)
    {
        ApiJsonValidator::validate($request->all(), [
            'search' => 'bail|string|nullable',
            'include_archived' => 'bail|string',
        ]);

        AuditLog::createFromRequest($request, 'DIALER-LEAVE-REQUEST-STATUSES:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerLeaveRequestStatus::query()
            ->select([
                'id',
                'name',
                'deleted_at',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('name')
            ->get();

        $allow_list = [
            'id',
            'name',
            'isActive',
            'isArchived',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Leave Request Statuses.xlsx");
    }

    /**
     * Add an item
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerLeaveRequestStatus::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Leave request status not found', 400);
            }
        } else {
            $item = new DialerLeaveRequestStatus();
        }

        ApiJsonValidator::validate($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_leave_request_statuses')->ignore($item) : Rule::unique('dialer_leave_request_statuses'),
            ],
            'isArchived' => 'bail|boolean',
        ]);

        try {
            DB::beginTransaction();

            $item->name = $request->input('name');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    if ($item->entries->count()) {
                        return ErrorResponse::json("This leave request status is in use by {$item->entries->count()} leave ".Str::plural('request', $item->entries->count()).".", 400);
                    }
                    $item->delete();
                } else {
                    $item->restore();
                }
            }
            $item->save();

            DB::commit();

            return $item;

        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorResponse::json('DB error: '.$e->getMessage(), 400);
        }
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        ApiJsonValidator::validate($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_leave_request_statuses,id',
        ]);

        $item = DialerLeaveRequestStatus::withTrashed()->find($item_id);
        if ($item->entries->count()) {
            return ErrorResponse::json("This leave request status is in use by {$item->entries->count()} leave ".Str::plural('request', $item->entries->count()).".", 400);
        }

        $item->delete();

        return response([]);
    }
}
