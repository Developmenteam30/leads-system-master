<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\DialerAccessRole;
use App\Models\DialerTeam;
use Illuminate\Database\Eloquent\Model;

class DialerAgentObserver extends GenericObserver
{
    /**
     * Handle the Model "creating" event.
     *
     * @param  Model  $item
     * @return void
     */
    public function creating(Model $item): void
    {
        // Automatically set default parameters when adding a new agent
        if (preg_match('/^([0-9]+)/', $item->agent_name, $matches)) {
            $company = Company::where('dialer_integer', $matches[1])
                ->first();

            if (!empty($company->idCompany)) {
                $item->company_id = $company->idCompany;
            }
        }

        if (empty($item->team_id)) {
            // Default to team "Aces" for training purposes
            $item->team_id = DialerTeam::DEFAULT_TEAM_ID;
        }

        if (empty($item->access_role_id)) {
            // Default to access role of Agent
            $item->access_role_id = DialerAccessRole::ACCESS_ROLE_AGENT;
        }

        parent::creating($item);
    }
}
