<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerCampaignDefault extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function campaign()
    {
        return $this->hasOne(DialerProduct::class, 'id', 'product_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'idCompany', 'company_id');
    }

    public function paymentType()
    {
        return $this->hasOne(DialerPaymentType::class, 'id', 'payment_type_id');
    }
}
