<?php

namespace App\Http\Controllers\Api\PettyCashEntries;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerPettyCashEntry;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PettyCashEntriesController extends BaseController
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
            'petty_cash_location_id' => 'bail|nullable|exists:dialer_petty_cash_locations,id',
            'petty_cash_vendor_id' => 'bail|nullable|exists:dialer_petty_cash_vendors,id',
            'petty_cash_reason_id' => 'bail|nullable|exists:dialer_petty_cash_reasons,id',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'DIALER-PETTY-CASH:LIST', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'petty_cash_location_id' => $request->input('petty_cash_location_id'),
            'petty_cash_vendor_id' => $request->input('petty_cash_vendor_id'),
            'petty_cash_reason_id' => $request->input('petty_cash_reason_id'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerPettyCashEntry::query()
            ->select([
                'id',
                'date',
                'amount',
                'type',
                DB::raw('(SELECT reason FROM dialer_petty_cash_reasons WHERE id = petty_cash_reason_id) as reason'),
                DB::raw('(SELECT location FROM dialer_petty_cash_locations WHERE id = petty_cash_location_id) as location'),
                DB::raw('(SELECT name FROM dialer_petty_cash_vendors WHERE id = petty_cash_vendor_id) as vendor'),
                DB::raw('(SELECT note FROM dialer_petty_cash_notes WHERE id = petty_cash_note_id) as note'),
                DB::raw('(SELECT agent_name FROM dialer_agents WHERE id = agent_id) as agent_name'),
                DB::raw('SUM(amount) OVER(ORDER BY date,id) as total'),
                DB::raw('ABS(amount) as absAmount'),
                'deleted_at',
            ])
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate, $request) {
                $query->whereBetween('date', [
                    $startDate,
                    $endDate,
                ]);
            })
            ->when($request->filled('petty_cash_location_id'), function ($query) use ($request) {
                $query->where('petty_cash_location_id', $request->input('petty_cash_location_id'));
            })
            ->when($request->filled('petty_cash_reason_id'), function ($query) use ($request) {
                $query->where('petty_cash_reason_id', $request->input('petty_cash_reason_id'));
            })
            ->when($request->filled('petty_cash_vendor_id'), function ($query) use ($request) {
                $query->where('petty_cash_vendor_id', $request->input('petty_cash_vendor_id'));
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        $allow_list = [
            'id',
            'date',
            'agent_name',
            'reason',
            'location',
            'vendor',
            'note',
            'type',
            'amount',
            'absAmount',
            'total',
            'isActive',
            'isArchived',
        ];

        $datatable = [
            'columns' => array_merge([
                ['label' => 'ID', 'field' => 'id', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Date', 'field' => 'date', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Entered By', 'field' => 'agent_name', 'displayFormat' => 'text',],
                ['label' => 'Location', 'field' => 'location', 'displayFormat' => 'text',],
                ['label' => 'Vendor', 'field' => 'vendor', 'displayFormat' => 'text',],
                ['label' => 'Reason', 'field' => 'reason', 'displayFormat' => 'text',],
            ],
                $request->filled('export') ? [
                    ['label' => 'Notes', 'field' => 'note', 'displayFormat' => 'text',],
                ] : [],
                [
                    ['label' => 'Amount', 'field' => 'amount', 'displayFormat' => 'accounting'],
                    $request->anyFilled([
                        'start_date',
                        'end_date',
                        'petty_cash_location_id',
                        'petty_cash_reason_id',
                        'petty_cash_vendor_id',
                    ]) ? [] : ['label' => 'Balance', 'field' => 'total', 'displayFormat' => 'accounting'],
                    ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
                ],
            ),
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Petty Cash.xlsx");
    }


    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerPettyCashEntry::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Petty Cash entry not found', 400);
            }
        } else {
            $item = new DialerPettyCashEntry();
            $item->agent_id = $request->user()->id;
        }

        $validator = Validator::make($request->all(), [
            'date' => 'bail|required|date',
            'petty_cash_location_id' => "bail|required|integer|exists:dialer_petty_cash_locations,id",
            'petty_cash_vendor_id' => "bail|required|integer|exists:dialer_petty_cash_vendors,id",
            'petty_cash_reason_id' => "bail|required|integer|exists:dialer_petty_cash_reasons,id",
            'petty_cash_note_id' => "bail|nullable|integer|exists:dialer_petty_cash_notes,id",
            'absAmount' => "bail|required|min:0.01|regex:/^\d+(\.\d{1,2})?$/",
            'type' => 'bail|string|required|in:in,out',
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $amount = abs($request->input('absAmount'));
        if ($request->input('type') == 'out') {
            $amount *= -1;
        }

        DB::transaction(function () use ($item, $request, $amount) {
            $item->date = $request->input('date');
            $item->amount = $amount;
            $item->petty_cash_location_id = $request->input('petty_cash_location_id');
            $item->petty_cash_vendor_id = $request->input('petty_cash_vendor_id');
            $item->petty_cash_reason_id = $request->input('petty_cash_reason_id');
            $item->petty_cash_note_id = $request->input('petty_cash_note_id');
            $item->type = $request->input('type');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    $item->delete();
                } else {
                    $item->restore();
                }
            }
            $item->save();
        });

        return response([]);
    }
}
