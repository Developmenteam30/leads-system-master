<?php

namespace App\Http\Controllers\Api\Reports;

use App\Models\DialerAgentPerformance;
use App\Models\DialerHoliday;
use App\Models\DialerHolidayList;
use App\Models\DialerLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class DatabaseCountsController extends BaseController
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

        $startDate = Carbon::parse($request->input('start_date'), new \DateTimeZone(config('settings.timezone.local')))->startOfWeek();
        $endDate = Carbon::parse($request->input('end_date'), new \DateTimeZone(config('settings.timezone.local')))->endOfWeek();
        $endDate->setTime(23, 59, 59); // To account for the last day

        $hours = (object) [
            'mon' => null,
            'tue' => null,
            'wed' => null,
            'thu' => null,
            'fri' => null,
            'sat' => null,
            'sun' => null,
        ];

        $dispos = (object) [
            'mon' => null,
            'tue' => null,
            'wed' => null,
            'thu' => null,
            'fri' => null,
            'sat' => null,
            'sun' => null,
        ];

        // Use min() to hide future dates
        $period = CarbonPeriod::since($startDate)->days(1)->until($endDate->min(Carbon::now(new \DateTimeZone(config('settings.timezone.local')))));
        $holidayFilter = function ($date) {
            // Skip US holidays
            $holiday = DialerHoliday::query()
                ->whereDate('holiday', $date)
                ->whereHas('holidayLists', function (Builder $query) {
                    $query->where('holiday_list_id', DialerHolidayList::US_ID);
                })
                ->exists();

            return !$holiday;
        };
        $period->filter($holidayFilter);


        foreach ($period as $key => $date) {
            $day = strtolower($date->format('D'));

            $count = DialerLog::query()
                ->whereNotNull('year')
                ->whereBetween('time_stamp', [
                    $date->startOfDay()->format('Y-m-d H:i:s'),
                    $date->endOfDay()->format('Y-m-d H:i:s'),
                ])
                ->count();

            if ($count > 1000) {
                $dispos->{$day} = true;
            } else {
                $dispos->{$day} = false;
            }

            $count = DialerAgentPerformance::query()
                ->whereDate('file_date', $date)
                ->count();

            if ($count > 40) {
                $hours->{$day} = true;
            } else {
                $hours->{$day} = false;
            }
        }

        return [
            'dispos' => $dispos,
            'hours' => $hours,
        ];

    }
}
