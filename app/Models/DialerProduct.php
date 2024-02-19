<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerProduct extends Model
{
    public $timestamps = false;

    const MEDICARE_INTEGRIANT = 1;
    const FINAL_EXPENSE_INTEGRIANT = 2;
    const ACA_INTEGRIANT = 3;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
