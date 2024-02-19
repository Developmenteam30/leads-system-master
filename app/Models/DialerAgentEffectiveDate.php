<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DialerAgentEffectiveDate extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function agentType()
    {
        return $this->hasOne(DialerAgentType::class, 'id', 'agent_type_id');
    }

    public function paymentType()
    {
        return $this->hasOne(DialerPaymentType::class, 'id', 'payment_type_id');
    }

    public function product()
    {
        return $this->hasOne(DialerProduct::class, 'id', 'product_id');
    }

    public function agent()
    {
        return $this->hasOne(DialerAgent::class, 'id', 'agent_id');
    }

    public function terminationReason()
    {
        return $this->hasOne(DialerAgentTerminationReason::class, 'id', 'termination_reason_id');
    }

    public static function createDefaultEntry($agent, $start_date): DialerAgentEffectiveDate
    {
        $effectiveDate = new DialerAgentEffectiveDate();
        $effectiveDate->agent_id = $agent->id;
        $effectiveDate->agent_type_id = DialerAgentType::AGENT;
        $effectiveDate->start_date = $start_date;
        $effectiveDate->is_training = 1;

        if (!empty($agent->company_id)) {
            $default = DialerCampaignDefault::query()
                ->where('company_id', $agent->company_id)
                ->where('campaign_id', DialerProduct::MEDICARE_INTEGRIANT) // Default everyone to Medicare for now per Tom
                ->first();

            $effectiveDate->product_id = $default->campaign_id ?? null;
            $effectiveDate->payment_type_id = $default->payment_type_id ?? null;
            $effectiveDate->billable_rate = $default->billable_rate ?? null;
            $effectiveDate->payable_rate = $default->payable_rate ?? null;
            $effectiveDate->bonus_rate = $default->bonus_rate ?? null;
        }

        $effectiveDate->save();

        return $effectiveDate;
    }
}
