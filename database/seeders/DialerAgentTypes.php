<?php

namespace Database\Seeders;

use App\Models\DialerAccessRole;
use App\Models\DialerAgentType;
use App\Models\DialerTeam;
use Illuminate\Database\Seeder;

class DialerAgentTypes extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entry = new DialerAgentType();
        $entry->id = DialerAgentType::AGENT;
        $entry->name = 'Agent';
        $entry->save();

        $entry = new DialerAgentType();
        $entry->id = DialerAgentType::VISIBLE_EMPLOYEE;
        $entry->name = 'Employee';
        $entry->save();

        $entry = new DialerAgentType();
        $entry->id = DialerAgentType::USER;
        $entry->name = 'User';
        $entry->save();
    }
}
