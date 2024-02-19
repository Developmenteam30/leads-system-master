<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerHoliday extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function holidayLists(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(DialerHolidayList::class, 'dialer_holidays_holiday_lists', 'holiday_id', 'holiday_list_id')->using(DialerHolidayHolidayList::class);
    }
}
