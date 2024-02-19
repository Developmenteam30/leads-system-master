<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerDocumentType extends Model
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
        return $this->hasMany(DialerDocument::class, 'document_type_id', 'id');
    }

    public function getIsActiveAttribute()
    {
        return !$this->trashed();
    }

    public function getIsArchivedAttribute()
    {
        return $this->trashed();
    }

    public static function getDocumentableType($slug)
    {
        switch ($slug) {
            case 'agent':
                $class = DialerAgent::class;
                break;

            case 'leave_request':
                $class = DialerLeaveRequest::class;
                break;

            case 'petty_cash_entry':
                $class = DialerPettyCashEntry::class;
                break;

            default:
                $class = null;
        }

        return $class;
    }
}
