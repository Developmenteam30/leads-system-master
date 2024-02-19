<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerAgentPerformance extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'billable_time' => 'double',
        'billable_time_override' => 'double',
        'bonus_amount' => 'double',
        'calls' => 'int',
        'contacts' => 'int',
        'failed_transfers' => 'int',
        'net_time' => 'int',
        'others' => 'int',
        'others_pct' => 'double',
        'over_20_min' => 'int',
        'over_60_min' => 'int',
        'pause_pct' => 'double',
        'pause_time' => 'int',
        'payable_amount' => 'double',
        'talk_pct' => 'double',
        'talk_time' => 'int',
        'total_time' => 'int',
        'transfer_pct' => 'double',
        'transfers' => 'int',
        'under_6_min' => 'int',
        'voicemail' => 'int',
        'voicemail_pct' => 'double',
        'wait_pct' => 'double',
        'wait_time' => 'int',
        'wrapup_pct' => 'double',
        'wrapup_time' => 'int',
    ];

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    /**
     * Scope a query to join effective date rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeJoinEffectiveDates($query)
    {
        return $query->leftJoin('dialer_agent_effective_dates', function ($join) {
            $join->on('dialer_agent_performances.agent_id', 'dialer_agent_effective_dates.agent_id')
                ->where(function ($where) {
                    $where->whereColumn('dialer_agent_effective_dates.start_date', '<=', 'dialer_agent_performances.file_date');
                })
                ->where(function ($where) {
                    $where->whereNull('dialer_agent_effective_dates.end_date')
                        ->orWhereColumn('dialer_agent_effective_dates.end_date', '>=', 'dialer_agent_performances.file_date');
                });
        });
    }
}
