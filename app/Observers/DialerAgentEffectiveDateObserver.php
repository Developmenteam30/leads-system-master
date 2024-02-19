<?php

namespace App\Observers;

use App\Models\DialerAgentPerformance;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DialerAgentEffectiveDateObserver extends GenericObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $item): void
    {
        parent::created($item);

        // Ensure an entry exists on the hire date for training calculation purposes
        self::populateDialerAgentPerformance($item);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $item): void
    {
        parent::updated($item);

        // Ensure an entry exists on the hire date for training calculation purposes
        self::populateDialerAgentPerformance($item);
    }

    private function populateDialerAgentPerformance($item): void
    {
        // Ensure an entry exists on the hire date for training calculation purposes
        if (!empty($item->start_date)) {
            $period = CarbonPeriod::between(Carbon::parse($item->start_date, new \DateTimeZone(config('settings.timezone.local'))), Carbon::now(new \DateTimeZone(config('settings.timezone.local'))));
            $period->filter(function ($date) {
                return $date->isWeekday();
            });

            foreach ($period as $date) {
                DialerAgentPerformance::insertOrIgnore(
                    [
                        'agent_id' => $item->agent_id,
                        'file_date' => $date->format('Y-m-d'),
                        'internal_campaign_id' => $item->product_id,
                    ]
                );
            }
        }
    }
}
