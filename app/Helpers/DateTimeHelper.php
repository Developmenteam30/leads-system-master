<?php

namespace App\Helpers;

use Carbon\CarbonInterval;

class DateTimeHelper
{
    public static function seconds2time($value): string
    {
        if (empty($value)) {
            return '';
        }

        $interval = CarbonInterval::seconds($value)->cascade();

        return !empty($interval) ? sprintf("%s%s:%s",
            !empty(floor($interval->totalHours)) ? floor($interval->totalHours).':' : '',
            str_pad($interval->minutes, 2, "0", STR_PAD_LEFT),
            str_pad($interval->seconds, 2, "0", STR_PAD_LEFT))
            : '';
    }
}
