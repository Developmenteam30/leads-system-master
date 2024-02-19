<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerLeaveRequestStatus extends Model
{
    use SoftDeletes;

    const PENDING = 1;

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
        return $this->hasMany(DialerLeaveRequest::class, 'leave_request_status_id', 'id');
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
