<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Initialize extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DialerAccessRoles::class,
            DialerTeams::class,
            DialerAgentTypes::class,
        ]);
    }
}
