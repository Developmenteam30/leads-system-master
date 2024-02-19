<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerLog extends Model
{
    protected $primaryKey = 'call_id';
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function campaign()
    {
        return $this->hasOne(DialerExternalCampaign::class, 'id', 'campaign_id');
    }

    /**
     * Scope a query to only include records from a certain date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTimestampQuery($query, $date)
    {
        return $query->whereBetween('time_stamp', [
            $date . ' 00:00:00',
            $date . ' 23:59:59',
        ]);
    }
}
