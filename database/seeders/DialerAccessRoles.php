<?php

namespace Database\Seeders;

use App\Models\DialerAccessRole;
use Illuminate\Database\Seeder;

class DialerAccessRoles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entry = new DialerAccessRole();
        $entry->id = DialerAccessRole::ACCESS_ROLE_DEVELOPER;
        $entry->abbreviation = 'DEV';
        $entry->name = 'Developer';
        $entry->save();
    }
}
