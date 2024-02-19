<?php

namespace Database\Seeders;

use App\Models\DialerAccessRole;
use App\Models\DialerTeam;
use Illuminate\Database\Seeder;

class DialerTeams extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entry = new DialerTeam();
        $entry->id = DialerTeam::TEAM_OJT;
        $entry->name = 'OJT';
        $entry->save();
    }
}
