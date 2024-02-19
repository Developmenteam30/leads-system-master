<?php

namespace App\Console\Commands\Admin;

use Illuminate\Console\Command;

class PopulateTrainingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:populate-training';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate training flag';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $agents = \App\Models\DialerAgent::get();
        foreach ($agents as $agent) {
            print "{$agent->id} {$agent->agent_name}\r\n";

            $effective_date = \App\Models\DialerAgentEffectiveDate::query()
                ->where('agent_id', $agent->id)
                ->where('start_date', $agent->start_date)
                ->first();

            if ($effective_date) {
                $effective_date->is_training = 1;
                $effective_date->save();
            }
        }

        return true;
    }
}
