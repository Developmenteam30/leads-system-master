<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $table = 'auditlog';
    protected $primaryKey = 'logId';
    public $timestamps = false;

    protected $appends = [
        'id',
    ];

    public static function createFromRequest(Request $request, $action, array $payload = []): AuditLog
    {
        $log = new AuditLog();
        $log->action = $action;
        $log->ipaddress = $request->ip() ?? null;
        $log->agent_id = $request->user()->id ?? null;
        $log->timestamp = Carbon::now();
        $log->notes = !empty($payload) ? json_encode($payload) : null;
        $log->save();

        return $log;
    }
	
	function getIdAttribute()
    {
        return $this->logId;
    }

}
