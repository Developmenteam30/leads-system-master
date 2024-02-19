<?php

namespace Database\Seeders;

use App\Models\DialerPipResolution;
use Illuminate\Database\Seeder;

class DialerPipResolutions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entry = new DialerPipResolution();
        $entry->id = 1;
        $entry->resolution = 'Passed';
        $entry->save();

        $entry = new DialerPipResolution();
        $entry->id = 2;
        $entry->resolution = 'Failed';
        $entry->save();

        $entry = new DialerPipResolution();
        $entry->id = 3;
        $entry->resolution = 'Extended';
        $entry->save();
    }
}
