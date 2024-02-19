<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerEodReport extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = [
        'reportDate',
    ];

    public function managerAgent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'manager_agent_id');
    }

    public function team()
    {
        return $this->hasOne(DialerTeam::class, 'id', 'team_id');
    }

    public function getReportDateAttribute()
    {
        return CarbonImmutable::parse($this->created_at)->setTimezone(config('settings.timezone.local'));
    }
}
