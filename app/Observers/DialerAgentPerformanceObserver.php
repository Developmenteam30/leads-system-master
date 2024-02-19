<?php

namespace App\Observers;

use App\Models\DialerAgentPerformance;

class DialerAgentPerformanceObserver
{
    /**
     * Handle the DialerAgentPerformance "created" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function created(DialerAgentPerformance $dialerAgentPerformance)
    {
        //
    }

    /**
     * Handle the DialerAgentPerformance "updated" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function updated(DialerAgentPerformance $dialerAgentPerformance)
    {
        //
    }

    /**
     * Handle the DialerAgentPerformance "deleted" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function deleted(DialerAgentPerformance $dialerAgentPerformance)
    {
        //
    }

    /**
     * Handle the DialerAgentPerformance "restored" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function restored(DialerAgentPerformance $dialerAgentPerformance)
    {
        //
    }

    /**
     * Handle the DialerAgentPerformance "force deleted" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function forceDeleted(DialerAgentPerformance $dialerAgentPerformance)
    {
        //
    }

    /**
     * Handle the DialerAgentPerformance "saving" event.
     *
     * @param  \App\Models\DialerAgentPerformance  $dialerAgentPerformance
     * @return void
     */
    public function saving(DialerAgentPerformance $dialerAgentPerformance)
    {
        // We don't want to save zeros in the database as it messes with the Excel reports.
        $dialerAgentPerformance->billable_time_override = empty($dialerAgentPerformance->billable_time_override) ? null : $dialerAgentPerformance->billable_time_override;
    }
}
