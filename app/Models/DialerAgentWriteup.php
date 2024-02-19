<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerAgentWriteup extends Model
{
    use SoftDeletes;

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

    public function level()
    {
        return $this->hasOne(DialerAgentWriteupLevel::class, 'id', 'writeup_level_id');
    }

    public function reason()
    {
        return $this->hasOne(DialerAgentWriteupReason::class, 'id', 'reason_id');
    }

    public function reporter()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'reporter_agent_id');
    }
}
