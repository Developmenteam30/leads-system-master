<?php

namespace App\Console\Commands\Reports;

use App\Jobs\SendStatsReport;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendStatsReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:stats-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the daily stats report';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? Carbon::parse($this->argument('date')) : Carbon::now(new \DateTimeZone(config('settings.timezone.local')))->subDay();

        $log = new AuditLog();
        $log->action = 'EMAIL:STATS-REPORT';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'date' => $date->format('Y-m-d'),
        ]);
        $log->save();

        SendStatsReport::dispatch($date, $log->logId);

        return true;
    }
}
