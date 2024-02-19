<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerAgentPip extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date:Y-m-d',
    ];

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    public function reporter()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'reporter_agent_id');
    }

    public function reasons()
    {
        return $this->belongsToMany(DialerPipReason::class, 'dialer_agent_pips_dialer_pip_reasons', 'pip_id', 'reason_id')->using(DialerAgentPipsDialerPipsReasons::class);
    }

    public function resolution()
    {
        return $this->hasOne(DialerPipResolution::class, 'id', 'resolution_id');
    }


    public function getReasonIdsAttribute()
    {
        return $this->reasons->pluck('id')->toArray();
    }

    public function getReasonsArrayAttribute()
    {
        return $this->reasons->pluck('reason')->toArray();
    }

    public function getReasonsStringAttribute()
    {
        return implode(', ', $this->reasonsArray);
    }

    public function markAsFailed($user_id, $reason_id): void
    {
        $term_item = new DialerAgentTermination();
        $term_item->nominator_id = $user_id;
        $term_item->agent_id = $this->agent_id;
        $term_item->pip_issue_date = $this->start_date;
        $term_item->reason_id = $reason_id;
        $term_item->save();
    }

    public function markAsExtended($user_id): void
    {
        $this->end_date = $this->start_date->addDays(6);

        $new_item = new DialerAgentPip();
        $new_item->reporter_agent_id = $user_id;
        $new_item->start_date = $this->start_date->addWeek();
        $new_item->agent_id = $this->agent_id;
        $new_item->parent_id = $this->id;
        $new_item->save();
    }
}
