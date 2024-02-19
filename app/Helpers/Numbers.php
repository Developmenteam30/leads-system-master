<?php

namespace App\Helpers;

class Numbers
{
    public static function average($numerator, $denominator): float
    {
        if (empty($denominator)) {
            return 0.00;
        }

        return $numerator / $denominator;
    }

    public static function averageAndRound($numerator, $denominator, $decimals = 2): float
    {
        return round(self::average($numerator, $denominator), $decimals);
    }

    public static function averageAndRoundFormat($numerator, $denominator, $decimals = 2): string
    {
        return number_format(self::averageAndRound($numerator, $denominator, $decimals), $decimals);
    }

    public static function averagePercentageAndRound($numerator, $denominator, $decimals = 2): float
    {
        return round(self::average($numerator, $denominator) * 100, $decimals);
    }

    public static function roundAndFormat($value, $decimals = 2): string
    {
        return number_format(round($value, $decimals), $decimals);
    }

    public static function roundAndFormatCurrency($value, $decimals = 2): string
    {
        return '$'.self::roundAndFormat($value, $decimals);
    }
}
