<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Routing\Controller as BaseController;

class QuickJumpController extends BaseController
{
    private int $startOfWeek = CarbonInterface::MONDAY;
    private int $endOfWeek = CarbonInterface::SUNDAY;
    private int $startYear = 2022;
    private string $valueFormat = 'Y-m-d';

    public function index()
    {
        $options = collect([]);

        $today = CarbonImmutable::now()->setTimezone(config('settings.timezone.local'));

        $options->push([
            'text' => 'Today',
            'value' => $today->format($this->valueFormat).'|'.$today->format($this->valueFormat),
            'selected' => false,
        ]);

        $options->push([
            'text' => 'Yesterday',
            'value' => $today->subDay()->format($this->valueFormat).'|'.$today->subDay()->format($this->valueFormat),
            'selected' => false,
        ]);

        $options->push([
            'text' => 'This Week',
            'value' => $today->startOfWeek($this->startOfWeek)->format($this->valueFormat).'|'.$today->endOfWeek($this->endOfWeek)->format($this->valueFormat),
            'selected' => true,
        ]);

        $options->push([
            'text' => 'Last Week',
            'value' => $today->subWeek()->startOfWeek($this->startOfWeek)->format($this->valueFormat).'|'.$today->subWeek()->endOfWeek($this->endOfWeek)->format($this->valueFormat),
            'selected' => false,
        ]);

        for ($y = $today->year; $y >= $this->startYear; $y--) {
            $options->push([
                'text' => "${y} Year",
                'value' => $today->year($y)->startOfYear()->format($this->valueFormat).'|'.$today->year($y)->endOfYear()->format($this->valueFormat),
                'selected' => false,
            ]);

            for ($q = 4; $q > 0; $q--) {
                if ($y < $today->year || $q <= $today->quarter) {
                    $options->push([
                        'text' => "${y} Qtr ${q}",
                        'value' => $today->year($y)->month($q * 3)->startOfQuarter()->format($this->valueFormat).'|'.$today->year($y)->month($q * 3)->endOfQuarter()->format($this->valueFormat),
                        'selected' => false,
                    ]);

                    for ($m = $today->year($y)->month($q * 3)->endOfQuarter()->month; $m >= $today->year($y)->month($q * 3)->startOfQuarter()->month; $m--) {
                        if ($y < $today->year || $m <= $today->month) {
                            $options->push([
                                'text' => "${y}-".str_pad($m, 2, "0", STR_PAD_LEFT),
                                'value' => $today->year($y)->month($m)->startOfMonth()->format($this->valueFormat).'|'.$today->year($y)->month($m)->endOfMonth()->format($this->valueFormat),
                                'selected' => false,
                            ]);
                        }
                    }

                }
            }
        }

        return $options;
    }

    public function weeks()
    {
        $options = collect([]);

        $date = Carbon::now()->setTimezone(config('settings.timezone.local'));

        do {

            $options->push([
                'text' => $date->startOfWeek($this->startOfWeek)->format('n/j/y').' to '.$date->endOfWeek($this->endOfWeek)->format('n/j/y'),
                'value' => $date->startOfWeek($this->startOfWeek)->format($this->valueFormat).'|'.$date->endOfWeek($this->endOfWeek)->format($this->valueFormat),
                'selected' => false,
            ]);

            $date->subWeek();
        } while ($date->year >= $this->startYear);

        return $options;
    }

    public function months()
    {
        $options = collect([]);

        $date = Carbon::now()->setTimezone(config('settings.timezone.local'));

        do {

            $options->push([
                'text' => "{$date->format('Y')}-{$date->format('m')}",
                'value' => $date->startOfMonth()->format($this->valueFormat).'|'.$date->endOfMonth()->format($this->valueFormat),
                'selected' => false,
            ]);

            $date->subMonthNoOverflow();
        } while ($date->year >= $this->startYear);

        return $options;
    }
}
