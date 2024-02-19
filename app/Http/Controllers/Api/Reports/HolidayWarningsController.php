<?php

namespace App\Http\Controllers\Api\Reports;

use App\Models\DialerDispositionLog;
use App\Models\DialerHoliday;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HolidayWarningsController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'))->startOfWeek();
        $endDate = Carbon::parse($request->input('end_date'))->endOfWeek();

        $holiday = DialerHoliday::query()
            ->select([
                DB::raw('IF(DAYOFWEEK(holiday) >= 6,1,0) AS rollover'),
            ])
            ->whereBetween('holiday', [
                $startDate,
                $endDate,
            ])
            ->first();

        return [
            'holiday' => !empty($holiday),
            'rollover' => !empty($holiday) && $holiday->rollover,
        ];
    }
}
