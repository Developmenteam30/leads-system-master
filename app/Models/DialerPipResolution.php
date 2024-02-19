<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialerPipResolution extends Model
{
    use SoftDeletes;

    const PASS = 1;
    const FAIL = 2;
    const EXTEND = 3;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
