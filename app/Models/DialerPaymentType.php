<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerPaymentType extends Model
{
    const HOURLY = 1;
    const FINAL_TRANSFER = 2;
    const SALARY = 3;

    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
