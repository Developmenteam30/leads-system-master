<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerLeaveRequest extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_DENIED = 3;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $appends = [
        'formattedStartDate',
        'formattedStartTime',
        'formattedEndDate',
        'formattedEndTime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    public function documents()
    {
        return $this->morphMany(DialerDocument::class, 'documentable');
    }

    public function reviewer()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'reviewer_agent_id');
    }

    public function status()
    {
        return $this->hasOne(DialerLeaveRequestStatus::class, 'id', 'leave_request_status_id');
    }

    public function type()
    {
        return $this->hasOne(DialerLeaveRequestType::class, 'id', 'leave_request_type_id');
    }

    public function getFormattedStartDateAttribute()
    {
        $start_time = CarbonImmutable::parse($this->start_time, 'UTC');

        return $start_time->timezone(config('settings.timezone.belize'))->format('Y-m-d');
    }

    public function getFormattedEndDateAttribute()
    {
        $end_time = CarbonImmutable::parse($this->end_time, 'UTC');

        return $end_time->timezone(config('settings.timezone.belize'))->format('Y-m-d');
    }

    public function getFormattedStartTimeAttribute()
    {
        if (in_array($this->leave_request_type_id, [DialerLeaveRequestType::VACATION, DialerLeaveRequestType::PTO])) {
            $start_time = CarbonImmutable::parse($this->start_time, 'UTC')->timezone(config('settings.timezone.belize'));

            return $start_time->isStartOfDay() ? '' : $start_time->format('h:i a');
        }

        return null;
    }

    public function getFormattedEndTimeAttribute()
    {
        if (in_array($this->leave_request_type_id, [DialerLeaveRequestType::VACATION, DialerLeaveRequestType::PTO])) {
            $end_time = CarbonImmutable::parse($this->end_time, 'UTC')->timezone(config('settings.timezone.belize'));

            return $end_time->isStartOfDay() ? '' : $end_time->format('h:i a');
        }

        return null;
    }
}
