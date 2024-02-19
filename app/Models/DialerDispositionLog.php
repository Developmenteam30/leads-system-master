<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerDispositionLog extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    public function status()
    {
        return $this->hasOne(DialerStatus::class, 'id', 'status_id');
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
            $join->on('dialer_disposition_logs.agent_id', 'dialer_agent_effective_dates.agent_id')
                ->where(function ($where) {
                    $where->whereNull('dialer_agent_effective_dates.start_date')
                        ->orWhereColumn('dialer_agent_effective_dates.start_date', '<=', 'dialer_disposition_logs.file_date');
                })
                ->where(function ($where) {
                    $where->whereNull('dialer_agent_effective_dates.end_date')
                        ->orWhereColumn('dialer_agent_effective_dates.end_date', '>=', 'dialer_disposition_logs.file_date');
                });
        });
    }
}
