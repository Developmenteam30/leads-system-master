<?php

namespace App\Http\Controllers\Api\PettyCashEntries;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerPettyCashNote;
use App\Models\DialerPettyCashReason;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PettyCashNotesController extends BaseController
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

        AuditLog::createFromRequest($request, 'DIALER-PETTY-CASH-NOTES:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerPettyCashNote::query()
            ->select([
                'id',
                'note',
                'deleted_at',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('note', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('note')
            ->get();

        $allow_list = [
            'id',
            'note',
            'isActive',
            'isArchived',
        ];

        $datatable = [
            'columns' => array_merge([
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Note', 'field' => 'note'],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ]),
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Petty Cash Notes.xlsx");
    }


    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerPettyCashNote::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Petty Cash note not found', 400);
            }
        } else {
            $item = new DialerPettyCashNote();
        }

        $validator = Validator::make($request->all(), [
            'note' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_petty_cash_notes')->ignore($item) : Rule::unique('dialer_petty_cash_notes'),
            ],
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            DB::beginTransaction();

            $item->note = $request->input('note');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    if ($item->entries->count()) {
                        return ErrorResponse::json("This note is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
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
            'id' => 'required|bail|exists:dialer_petty_cash_notes,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerPettyCashNote::withTrashed()->find($item_id);
        if ($item->entries->count()) {
            return ErrorResponse::json("This note is in use by {$item->entries->count()} petty cash ".Str::plural('entry', $item->entries->count()).".", 400);
        }
        $item->delete();

        return response([]);
    }
}
