<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerBillableTransfer extends Model
{
    const BILLABLE_RATE = 11.00;
    const PAYABLE_RATE = 7.50;

    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
