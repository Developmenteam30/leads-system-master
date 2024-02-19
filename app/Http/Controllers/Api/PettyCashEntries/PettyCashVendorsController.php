<?php

namespace App\Http\Controllers\Api\PettyCashEntries;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerPettyCashReason;
use App\Models\DialerPettyCashVendor;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PettyCashVendorsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'bail|string|nullable',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        AuditLog::createFromRequest($request, 'DIALER-PETTY-CASH-VENDORS:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerPettyCashVendor::query()
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
            'columns' => array_merge([
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Name', 'field' => 'name'],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ]),
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Petty Cash Vendors.xlsx");
    }


    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerPettyCashVendor::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Petty Cash vendor not found', 400);
            }
        } else {
            $item = new DialerPettyCashVendor();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_petty_cash_vendors')->ignore($item) : Rule::unique('dialer_petty_cash_vendors'),
            ],
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            DB::beginTransaction();

            $item->name = $request->input('name');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    if ($item->entries->count()) {
                        return ErrorResponse::json("This vendor is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
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
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_petty_cash_vendors,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerPettyCashVendor::withTrashed()->find($item_id);
        if ($item->entries->count()) {
            return ErrorResponse::json("This vendor is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
        }
        $item->delete();

        return response([]);
    }
}
