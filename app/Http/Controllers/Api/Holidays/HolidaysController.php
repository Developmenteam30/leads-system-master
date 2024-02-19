<?php

namespace App\Http\Controllers\Api\Holidays;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerHoliday;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HolidaysController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-HOLIDAYS:LIST');

        $items = DialerHoliday::query()
            ->orderBy('holiday', 'DESC')
            ->get();

        $allow_list = [
            'id',
            'holiday',
            'multiplier',
            'name',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Date', 'field' => 'holiday',],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Multiplier', 'field' => 'multiplier',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Holidays.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerHoliday::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Holiday level not found', 400);
            }
        } else {
            $item = new DialerHoliday();
        }

        $validator = Validator::make($request->all(), [
            'holiday' => [
                'bail',
                'required',
                'date',
                !empty($item) ? Rule::unique('dialer_holidays')->ignore($item) : Rule::unique('dialer_holidays'),
            ],
            'name' => 'bail|required|string|max:255',
            'multiplier' => [
                'bail',
                'required',
                'regex:/^[0-9](\.[0-9])?$/',
                'min: 0.0',
                'max: 9.9',
            ],
        ], [
            'multiplier.regex' => 'Multiplier must be a numeric value in the form of #.#',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->holiday = $request->input('holiday');
            $item->name = $request->input('name');
            $item->multiplier = $request->input('multiplier');
            $item->save();
        });

        return response([]);
    }
}
