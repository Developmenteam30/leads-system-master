<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerPettyCashReason extends Model
{
    use SoftDeletes;

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

    public function entries(): HasMany
    {
        return $this->hasMany(DialerPettyCashEntry::class, 'petty_cash_reason_id', 'id');
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
