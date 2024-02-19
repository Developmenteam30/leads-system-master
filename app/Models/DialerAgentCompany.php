<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DialerAgentCompany extends Pivot
{
    protected $table = 'dialer_agent_companies';
    public $timestamps = false;
}
