<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'idCompany';

    const DIALER_REPORT_TYPE_BILLABLE = 'billable';
    const DIALER_REPORT_TYPE_PAYABLE = 'payable';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
