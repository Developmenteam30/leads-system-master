<?php

namespace App\Services;

use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentType;
use App\Models\DialerExternalCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateEmployeeHoursService
{
    /**
     * @throws \Throwable
     */
    public static function handle(Carbon $date)
    {
        try {
            DB::beginTransaction();

            if ($date->dayOfWeek >= 1 && $date->dayOfWeek <= 5) {
                $agents = DialerAgent::query()
                    ->select([
                        'dialer_agents.*',
                        'dialer_agent_effective_dates.product_id',
                    ])
                    ->whereIn('agent_type_id', [DialerAgentType::VISIBLE_EMPLOYEE])
                    ->isActiveForDate($date)
                    ->get();

                if ($agents->isNotEmpty()) {
                    $agents->each(function ($agent) use ($date) {

                        Log::debug("{$agent->id} {$agent->agent_name}");

                        $record = DialerAgentPerformance::firstOrNew(
                            [
                                'agent_id' => $agent->id,
                                'file_date' => $date->format('Y-m-d'),
                                'internal_campaign_id' => $agent->product_id,
                            ]
                        );

                        $record->billable_time = 8 * 60;
                        $record->billable_time_override = 8 * 60;
                        $record->save();
                    });
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");

            throw $e;
        }
    }
}
