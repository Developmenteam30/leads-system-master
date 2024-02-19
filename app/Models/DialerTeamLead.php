<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerTeamLead extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function team()
    {
        return $this->hasOne(DialerTeam::class, 'id', 'team_id');
    }

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }
}
