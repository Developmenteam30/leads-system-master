<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\ProcessAgentProductivityImport;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AgentProductivityAPICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:agent-productivity-api {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agent productivity from Convoso API';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date')) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')))->subDay();

        ProcessAgentProductivityImport::dispatch(
            date: $date,
        );

        return true;
    }
}
