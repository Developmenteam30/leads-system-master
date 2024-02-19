<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerAgentTermination extends Model
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
        return $this->belongsTo(DialerAgent::class, 'agent_id');
    }

    public function reason()
    {
        return $this->belongsTo(DialerAgentTerminationReason::class, 'reason_id');
    }

    public function nominator()
    {
        return $this->belongsTo(DialerAgent::class, 'nominator_id');
    }
}
