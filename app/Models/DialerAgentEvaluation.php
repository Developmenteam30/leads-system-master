<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerAgentEvaluation extends Model
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

    public function reporter()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'reporter_agent_id');
    }
}
