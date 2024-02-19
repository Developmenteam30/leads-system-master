<?php

namespace App\Http\Controllers\Api\Holidays;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerHolidayList;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HolidayListsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-HOLIDAYS-LIST:LIST');

        $items = DialerHolidayList::query()
            ->with('holidays')
            ->get()
            ->append([
                'holidays_ids',
                'holidays_string',
            ]);

        $allow_list = [
            'id',
            'name',
            'holidays_ids',
            'holidays_string',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Holidays', 'field' => 'holidays_string'],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Holiday Lists.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerHolidayList::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Holiday list not found', 400);
            }
        } else {
            $item = new DialerHolidayList();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_holiday_lists')->ignore($item) : Rule::unique('dialer_holiday_lists'),
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($request, $item) {
            $item->name = $request->input('name');
            $item->save();

            $item->holidays()->sync($request->input('holidays_ids'));
        });

        return response([]);
    }
}
