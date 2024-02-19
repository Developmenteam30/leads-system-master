<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerAgentType extends Model
{
    const AGENT = 1;
    const VISIBLE_EMPLOYEE = 2;
    const USER = 4;

    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
