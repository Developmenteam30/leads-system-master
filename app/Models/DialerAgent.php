<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class DialerAgent extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    const PAYROLL_COMPANY_IDS = [
        16, // Acquiro Media - WFH (2)
        29, // Acqurio Media - In House (5)
        34, // Acquiro Media - Corporate (8)
        35, // Acquiro Media - San Ignacio (9)
    ];
    const TRAINING_PAYABLE_RATE = 2.50; // 6356: Override the payable rate for training hours
    const TRAINING_BILLABLE_RATE = 10.00; // 6356: Override the billable rate for training hours
    const TRAINING_BILLABLE_RATE_WEEK1 = 8.00; // 7615: New billable training logic
    const TRAINING_BILLABLE_RATE_WEEK2 = 9.00; // 7615: New billable training logic
    const INTEGRIANT_HOLIDAY_RATE_15_MULTIPLIER = 16;
    const INTEGRIANT_HOLIDAY_RATE_20_MULTIPLIER = 19;
    const PAY_BUMP_AMOUNT = 1.00; // 6344: Pay increase after 60-day probation period
    const OVERTIME_BILLABLE_RATE = 16; // 6356: Overtime pay
    const OVERTIME_PAYABLE_HOURS = 45;
    const OVERTIME_PAYABLE_HOURS_ACA = 35;
    const OVERTIME_BILLABLE_HOURS = 40;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function agentType()
    {
        return $this->hasOne(DialerAgentType::class, 'id', 'agent_type_id');
    }

    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'dialer_agent_companies', 'agent_id', 'company_id')->using(DialerAgentCompany::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'idCompany', 'company_id');
    }

    public function team()
    {
        return $this->hasOne(DialerTeam::class, 'id', 'team_id');
    }

    public function effectiveDates()
    {
        return $this->hasMany(DialerAgentEffectiveDate::class, 'agent_id', 'id');
    }

    public function accessRole()
    {
        return $this->hasOne(DialerAccessRole::class, 'id', 'access_role_id');
    }

    public function latestActiveEffectiveDate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DialerAgentEffectiveDate::class, 'agent_id', 'id')->latestOfMany('start_date');
    }

    public function paymentType()
    {
        return $this->hasOne(DialerPaymentType::class, 'id', 'payment_type_id');
    }

    public function product()
    {
        return $this->hasOne(DialerProduct::class, 'id', 'product_id');
    }

    public function performances()
    {
        return $this->hasMany(DialerAgentPerformance::class, 'agent_id', 'id');
    }

    public function documents()
    {
        return $this->morphMany(DialerDocument::class, 'documentable');
    }

    public function leaveRequests()
    {
        return $this->hasMany(DialerLeaveRequest::class, 'agent_id', 'id');
    }

    public function firstPerformanceDate()
    {
        return $this->performances()->one()->ofMany([
            'file_date' => 'min',
        ], function ($query) {
            $query->where('billable_time', '>', 0);
        });
    }

    public function getEffectiveValuesForDate($date = null)
    {
        if (empty($date)) {
            $date = Carbon::now()->setTime(0, 0);
        } else {
            $date = Carbon::parse($date, 'UTC')->timezone('UTC')->setTime(0, 0);
        }

        return $this->effectiveDates->filter(function ($value) use ($date) {

            if (!empty($value->start_date)) {
                try {
                    $value->start_date = Carbon::parse($value->start_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }
            if (!empty($value->end_date)) {
                try {
                    $value->end_date = Carbon::parse($value->end_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }

            return (empty($value->start_date) || $value->start_date->lte($date)) &&
                (empty($value->end_date) || $value->end_date->gte($date));
        })->first();
    }

    public function getEffectiveValuesForDateRange($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate, 'UTC')->timezone('UTC')->setTime(0, 0);
        $endDate = Carbon::parse($endDate, 'UTC')->timezone('UTC')->setTime(0, 0);

        return $this->effectiveDates->filter(function ($value) use ($startDate, $endDate) {

            if (!empty($value->start_date)) {
                try {
                    $value->start_date = Carbon::parse($value->start_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }
            if (!empty($value->end_date)) {
                try {
                    $value->end_date = Carbon::parse($value->end_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }

            return (empty($value->start_date) || $value->start_date->lte($endDate)) &&
                (empty($value->end_date) || $value->end_date->gte($startDate));
        })->first();
    }

    public function getEffectiveTrainingValuesForDateRange($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate, 'UTC')->timezone('UTC')->setTime(0, 0);
        $endDate = Carbon::parse($endDate, 'UTC')->timezone('UTC')->setTime(0, 0);

        return $this->effectiveDates->sortByDesc('start_date')->filter(function ($value) use ($startDate, $endDate) {

            if (!empty($value->start_date)) {
                try {
                    $value->start_date = Carbon::parse($value->start_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }
            if (!empty($value->end_date)) {
                try {
                    $value->end_date = Carbon::parse($value->end_date);
                } catch (\Throwable $e) {
                    // Do nothing
                }
            }

            return (empty($value->start_date) || $value->start_date->lte($endDate)) &&
                (empty($value->end_date) || $value->end_date->gte($startDate)) &&
                $value->is_training;
        })->first();
    }

    public function isTrainingForDateRange($startDate, $endDate): bool
    {
        $trainingRow = $this->getEffectiveTrainingValuesForDateRange($startDate, $endDate);
        if (empty($trainingRow)) {
            return false;
        }

        $startDate = CarbonImmutable::parse($startDate, 'UTC')->timezone('UTC')->setTime(0, 0);
        $trainingStartDate = $trainingRow->start_date ? CarbonImmutable::parse($trainingRow->start_date) : null;

        return !empty($trainingStartDate) &&
            ($startDate->format('YW') === $trainingStartDate->format('YW') ||
                $startDate->format('YW') === $trainingStartDate->addWeek()->format('YW'));
    }

    public function getEffectiveHireDateAttribute()
    {
        return $this->effectiveDates->sortBy('start_date')->first()->start_date ?? null;
    }

    public function getEffectiveTerminationDateAttribute()
    {
        return $this->mostRecentEffectiveDate->end_date ?? null;
    }

    public function getEffectiveTerminationReasonAttribute()
    {
        return $this->mostRecentEffectiveDate->terminationReason->reason ?? null;
    }

    public function getMostRecentEffectiveDateAttribute()
    {
        return $this->effectiveDates->sortByDesc(function (DialerAgentEffectiveDate $date, int $key) {
            return empty($date->end_date) ? '9999-99-99' : $date->end_date;
        })->first() ?? null;
    }

    public function getCompaniesListAttribute()
    {
        return $this->companies->pluck('idCompany')->toArray();
    }

    public function getAccessAreasListAttribute()
    {
        return $this->accessRole->accessAreas->pluck('slug')->toArray();
    }

    public function scopeCanLogin($query)
    {
        return $query->whereNotNull('access_role_id')->isActiveForDate(now(config('settings.timezone.local'))->format('Y-m-d'));
    }

    public function scopeIsActiveForDate($query, $date)
    {
        return $query->join('dialer_agent_effective_dates', function ($join) use ($date) {
            $join->on('dialer_agents.id', 'dialer_agent_effective_dates.agent_id')
                ->where(function ($where) use ($date) {
                    $where->whereNull('dialer_agent_effective_dates.start_date')
                        ->orWhere('dialer_agent_effective_dates.start_date', '<=', $date);
                })
                ->where(function ($where) use ($date) {
                    $where->whereNull('dialer_agent_effective_dates.end_date')
                        ->orWhere('dialer_agent_effective_dates.end_date', '>=', $date);
                });
        });
    }

    public function scopeIsActiveForDateRange($query, $startDate, $endDate)
    {
        return $query->join('dialer_agent_effective_dates', function ($join) use ($startDate, $endDate) {
            $join->on('dialer_agents.id', 'dialer_agent_effective_dates.agent_id')
                ->where(function ($where) use ($endDate) {
                    $where->whereNull('dialer_agent_effective_dates.start_date')
                        ->orWhere('dialer_agent_effective_dates.start_date', '<=', $endDate);
                })
                ->where(function ($where) use ($startDate) {
                    $where->whereNull('dialer_agent_effective_dates.end_date')
                        ->orWhere('dialer_agent_effective_dates.end_date', '>=', $startDate);
                });
        });
    }

    public function getAuthIdentifierName()
    {
        return 'email';
    }

    public function getAuthIdentifier()
    {
        return $this->email;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Do nothing
    }

    public function getRememberTokenName()
    {
        return null;
    }

    public function hasAccessToArea($slug): bool
    {
        return $this->accessAreasList && is_array($this->accessAreasList) && in_array($slug, $this->accessAreasList);
    }

    public function getVacationAccruedAttribute(): float
    {
        if (empty($this->effectiveHireDate)) {
            return 0.00;
        }

        try {
            $hireDate = CarbonImmutable::parse($this->effectiveHireDate);
            if ($hireDate->diffInDays(now(config('settings.timezone.local'))) < 90) {
                return 0.00;
            }

            return round($hireDate->diffInMonths(now(config('settings.timezone.local'))) * 6.68, 2);
        } catch (\Exception $e) {
            // Do nothing
        }

        return 0.00;
    }

    public function getSickAccruedAttribute(): float
    {
        // 7765: Sick is always 16 hours, based on calendar year, and resets on January 1st.
        return 16.00;
    }

    public function getPtoAccruedAttribute(): float
    {
        if (empty($this->effectiveHireDate)) {
            return 0.00;
        }

        try {
            $hireDate = CarbonImmutable::parse($this->effectiveHireDate);

            $currentDate = now(config('settings.timezone.local'));
            // 7765/8255/8382: Check if the hire date is 90 days ago
            if ($hireDate->diffInDays($currentDate) < 90) {
                return 0.00;
            }
            $yearsSinceHire = $hireDate->diffInYears(now(config('settings.timezone.local')));

            $agentTypeId = $this->latestActiveEffectiveDate->agent_type_id ?? null;

            $basePto = match ($agentTypeId) {
                DialerAgentType::AGENT => 8.00,
                DialerAgentType::VISIBLE_EMPLOYEE => 16.00,
                default => 0.00,
            };

            return $basePto * ($yearsSinceHire + 1);

        } catch (\Exception $e) {
            // Do nothing
        }

        return 0.00;
    }

    public function getVacationTakenAttribute()
    {
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::VACATION)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_APPROVED, DialerLeaveRequest::STATUS_PENDING])
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()) * 8);
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }

    public function getPtoTakenAttribute()
    {
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::PTO)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_APPROVED, DialerLeaveRequest::STATUS_PENDING])
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    // 7765: If the start time and end time are equal, it's a full day PTO request, so count it as 8 hours.
                    if ($item->start_time->eq($item->end_time)) {
                        $diff = 8;
                    } else {
                        $diff = $item->start_time->diffInHours($item->end_time);
                    }
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }

    public function getSickTakenAttribute()
    {
        // 7765: Sick is always 16 hours, based on calendar year, and resets on January 1st.
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::SICK)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_APPROVED, DialerLeaveRequest::STATUS_PENDING])
            ->where('start_time', '>=', CarbonImmutable::createMidnightDate(CarbonImmutable::today()->year, 1, 1, config('settings.timezone.belize')))
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()));
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }


    public function getSickPendingAttribute()
    {
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::SICK)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_PENDING])
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()));
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }

    public function getPtoPendingAttribute()
    {
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::SICK)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_PENDING])
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()));
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }

    public function getVacationPendingAttribute()
    {
        return $this->leaveRequests
            ->where('leave_request_type_id', DialerLeaveRequestType::SICK)
            ->whereIn('leave_request_status_id', [DialerLeaveRequest::STATUS_PENDING])
            ->reduce(function (int $carry, DialerLeaveRequest $item) {
                $diff = 0;
                try {
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()));
                } catch (\Throwable) {
                    // Do nothing
                }

                return $carry + $diff;
            }, 0);
    }

    public function getVacationRemainingAttribute(): float
    {
        $vacationAccrued = $this->getVacationAccruedAttribute();
        $vacationTaken = $this->getVacationTakenAttribute();

        $vacationAvailable = $vacationAccrued - $vacationTaken;

        return $vacationAvailable < 0 ? 0.00 : round($vacationAvailable, 2);
    }

    public function getSickRemainingAttribute(): float
    {
        $sickAccrued = $this->getSickAccruedAttribute();
        $sickTaken = $this->getSickTakenAttribute();

        $sickAvailable = $sickAccrued - $sickTaken;

        return $sickAvailable < 0 ? 0.00 : round($sickAvailable, 2);
    }

    public function getPtoRemainingAttribute(): float
    {
        $ptoAccrued = $this->getPtoAccruedAttribute();
        $ptoTaken = $this->getPtoTakenAttribute();

        $ptoAvailable = $ptoAccrued - $ptoTaken;

        return $ptoAvailable < 0 ? 0.00 : round($ptoAvailable, 2);
    }

    /**
     * Customize the email address ana name fields for Notifications.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        // Return email address and name...
        return [$this->email => $this->agent_name];
    }
}
