<?php

namespace App\Http\Controllers\Api\PettyCashEntries;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerPettyCashLocation;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PettyCashLocationsController extends BaseController
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

        AuditLog::createFromRequest($request, 'DIALER-PETTY-CASH-LOCATIONS:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerPettyCashLocation::query()
            ->select([
                'id',
                'location',
                'deleted_at',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('location', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('location')
            ->get();

        $allow_list = [
            'id',
            'location',
            'isActive',
            'isArchived',
        ];

        $datatable = [
            'columns' => array_merge([
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Location', 'field' => 'location'],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ]),
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Petty Cash Locations.xlsx");
    }


    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerPettyCashLocation::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Petty Cash Location not found', 400);
            }
        } else {
            $item = new DialerPettyCashLocation();
        }

        $validator = Validator::make($request->all(), [
            'location' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_petty_cash_locations')->ignore($item) : Rule::unique('dialer_petty_cash_locations'),
            ],
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            DB::beginTransaction();

            $item->location = $request->input('location');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    if ($item->entries->count()) {
                        return ErrorResponse::json("This location is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
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
            'id' => 'required|bail|exists:dialer_petty_cash_locations,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerPettyCashLocation::withTrashed()->find($item_id);
        if ($item->entries->count()) {
            return ErrorResponse::json("This location is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
        }
        $item->delete();

        return response([]);
    }
}
