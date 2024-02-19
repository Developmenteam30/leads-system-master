<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\SummarizeCallDetailLog;
use App\Models\DialerAgentPerformance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResummarizeCallDetailLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:resummarize-call-detail-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resummarize all past call detail logs';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dates = DialerAgentPerformance::query()
            ->groupBy('file_date')
            ->whereBetween('file_date', [
                '2023-05-15',
                '2023-05-30',
            ])
            ->orderBy('file_date', 'DESC')
            ->get('file_date');

        $dates->each(function ($date) {
            Log::info("ResummarizeCallDetailLogsCommand: Processing {$date->file_date}");
            SummarizeCallDetailLog::dispatch($date->file_date, null);
        });

        return true;
    }
}
