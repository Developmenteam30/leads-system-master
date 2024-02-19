<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerPettyCashEntry extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $appends = [
        'absAmount',
        'isActive',
        'isArchived',
    ];

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    public function location()
    {
        return $this->hasOne(DialerPettyCashLocation::class, 'id', 'petty_cash_location_id');
    }

    public function note()
    {
        return $this->hasOne(DialerPettyCashNote::class, 'id', 'petty_cash_note_id');
    }

    public function reason()
    {
        return $this->hasOne(DialerPettyCashReason::class, 'id', 'petty_cash_reason_id');
    }

    public function vendor()
    {
        return $this->hasOne(DialerPettyCashVendor::class, 'id', 'petty_cash_vendor_id');
    }

    public function getAbsAmountAttribute(): float|int
    {
        return abs($this->amount);
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
