<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerPettyCashLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = [
        'isActive',
        'isArchived',
    ];
    public function entries(): HasMany
    {
        return $this->hasMany(DialerPettyCashEntry::class, 'petty_cash_location_id', 'id');
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
