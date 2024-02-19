<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerOnscriptRecordingUploadLog extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function audit_log()
    {
        return $this->hasOne(AuditLog::class, 'logId', 'log_id');
    }

    public function dialer_log()
    {
        return $this->hasOne(DialerLog::class, 'call_id', 'call_id');
    }
}
