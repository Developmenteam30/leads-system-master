<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\ProcessCallDetailLogFile;
use App\Jobs\SummarizeCallDetailLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SummarizeCallDetailLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:summarize-call-detail-log {date} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summarize call detail log data into agent performance table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = Carbon::parse($this->argument('date'))->format('Y-m-d');
        $email = $this->argument('email');

        SummarizeCallDetailLog::dispatch($date, $email);

        return true;
    }
}
