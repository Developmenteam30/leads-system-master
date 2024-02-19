<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\ProcessAgentPerformanceFile;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AgentPerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:agent-performance {file} {date} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agent performance file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('file');
        $date = Carbon::parse($this->argument('date'));
        $email = $this->argument('email');

        if (!file_exists($file)) {
            $this->error('Input file does not exist.');
            die();
        }

        ProcessAgentPerformanceFile::dispatch($file, $date, $email);

        return true;
    }
}
