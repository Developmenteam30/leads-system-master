<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerTeam extends Model
{
    use SoftDeletes;

    const TEAM_OJT = 3;
    const DEFAULT_TEAM_ID = self::TEAM_OJT; // OJT

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $appends = [
        'isActive',
        'isArchived',
    ];

    public function leads()
    {
        return $this->belongsToMany(DialerAgent::class, 'dialer_team_leads', 'team_id', 'agent_id');
    }

    public function manager()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'manager_agent_id');
    }

    public function agents()
    {
        return $this->hasMany(DialerAgent::class, 'team_id', 'id');
    }

    public function getTeamLeadAgentIdsAttribute()
    {
        return $this->leads->pluck('id')->toArray();
    }

    public function getTeamLeadAgentNamesAttribute()
    {
        return $this->leads->pluck('agent_name')->toArray();
    }

    public function getTeamLeadAgentNamesStringAttribute()
    {
        return implode(', ', $this->team_lead_agent_names);
    }


    public function getIsActiveAttribute()
    {
        return !$this->trashed();
    }

    public function getIsArchivedAttribute()
    {
        return $this->trashed();
    }
}
