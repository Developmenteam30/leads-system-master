<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerHolidayList extends Model
{
    const BELIZE_ID = 1;
    const US_ID = 2;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function holidays()
    {
        return $this->belongsToMany(DialerHoliday::class, 'dialer_holidays_holiday_lists', 'holiday_list_id', 'holiday_id')->using(DialerHolidayHolidayList::class);
    }

    public function getHolidaysIdsAttribute()
    {
        return $this->holidays->pluck('id')->toArray();
    }

    public function getHolidaysNamesAttribute()
    {
        return $this->holidays->pluck('name')->toArray();
    }

    public function getHolidaysStringAttribute()
    {
        return implode(', ', $this->holidays_names);
    }
}
