<?php

namespace App\Datasets;

use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use App\Models\DialerLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceDetailDataset
{
    public static function getDailyValues($filters)
    {
        $firstCalls = DialerLog::query()
            ->select([
                'agent_id',
                DB::raw("MIN(time_stamp) AS time_stamp"),
            ])
            ->timestampQuery($filters['date']->format('Y-m-d'))
            ->groupBy('agent_id');

        $lastCalls = DialerLog::query()
            ->select([
                'agent_id',
                DB::raw("MAX(time_stamp) AS time_stamp"),
            ])
            ->timestampQuery($filters['date']->format('Y-m-d'))
            ->groupBy('agent_id');

        $firstDialerDate = DialerLog::query()
            ->select([
                'agent_id',
                DB::raw("MIN(time_stamp) AS time_stamp"),
            ])
            ->groupBy('agent_id');

        $items = DialerAgent::query()
            ->isActiveForDate($filters['date'])
            ->leftJoinSub($firstCalls, 'first_call', function ($join) {
                $join->on('first_call.agent_id', '=', 'dialer_agents.id');
            })
            ->leftJoinSub($lastCalls, 'last_call', function ($join) {
                $join->on('last_call.agent_id', '=', 'dialer_agents.id');
            })
            ->leftJoinSub($firstDialerDate, 'first_dialer_date', function ($join) {
                $join->on('first_dialer_date.agent_id', '=', 'dialer_agents.id');
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$filters['search'].'%');
            })
            ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
            ->select([
                'dialer_agents.id',
                'dialer_agents.agent_name',
                DB::raw('first_call.time_stamp AS first_call_time'),
                DB::raw('last_call.time_stamp AS last_call_time'),
                DB::raw('first_dialer_date.time_stamp AS first_dialer_date'),
            ])
            ->orderBy('dialer_agents.agent_name')
            ->get();

        $today = CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        $items->transform(function ($item) use ($filters, $today) {
            $item->status_numeric = ['1'];
            if (!empty($item->first_call_time)) {
                $firstCallTime = CarbonImmutable::parse($item->first_call_time, config('convoso.timezone'))->timezone(config('settings.timezone.local'));
                $item->first_call_time = $firstCallTime->format('g:i:s a');
            }

            if (!empty($item->last_call_time)) {
                $lastCallTime = CarbonImmutable::parse($item->last_call_time, config('convoso.timezone'))->timezone(config('settings.timezone.local'));
                $item->last_call_time = $lastCallTime->format('g:i:s a');
            }

            if (!empty($item->first_dialer_date)) {
                $firstDialerDate = CarbonImmutable::parse($item->first_dialer_date, config('convoso.timezone'))->timezone(config('settings.timezone.local'));
            }

            if (empty($firstCallTime)) {
                $item->status = 'ABSENT';
                $item->status_numeric = ['2'];
                if (!empty($firstDialerDate)) {
                    // Don't count the first 2 dialer days as late or early.
                    if ($filters['date']->lte($firstDialerDate->addDay())) {
                        $item->status = 'ABSENT (OJT)';
                    }
                }
            } elseif ($firstCallTime->gt($firstCallTime->setTime(9, 10))) {
                $item->status = 'LATE';
                $item->status_numeric = ['3'];
                if (!empty($firstDialerDate)) {
                    // Don't count the first 2 dialer days as late or early.
                    if ($firstCallTime->lte($firstDialerDate->addDay())) {
                        $item->status .= ' (OJT)';
                    }
                }
            }

            // Check if the report is being run for today so we don't flag someone as leaving early until the workday has finished.
            if (!$today->isSameDay($filters['date']) || ($today->isSameDay($filters['date']) && $today->gte($today->setTime(18, 00)))) {
                if (!empty($lastCallTime) && $lastCallTime->lt($lastCallTime->setTime(17, 50))) {
                    // Check if they were both late and left early.
                    if (in_array('3', $item->status_numeric)) {
                        $item->status = 'LATE + LEFT EARLY';
                        $item->status_numeric = ['3', '4'];
                    } else {
                        $item->status = 'LEFT EARLY';
                        $item->status_numeric = ['4'];
                    }
                    if (!empty($firstDialerDate)) {
                        // Don't count the first 2 dialer days as late or early.
                        if ($lastCallTime->lte($firstDialerDate->addDay())) {
                            $item->status .= ' (OJT)';
                        }
                    }
                }
            }

            return $item;
        });

        if (!empty($filters['statuses'])) {
            $statuses = explode(',', $filters['statuses']);
            $items = $items->filter(function ($item) use ($statuses) {
                return !empty(array_intersect($item->status_numeric, $statuses));
            })->values();
        }

        return $items;
    }
}
