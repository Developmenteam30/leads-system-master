<?php

namespace App\Console\Commands\Reports;

use App\Jobs\SendMissingDispoReportJob;
use App\Models\AuditLog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendMissingDispoReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:missing-dispo-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a notification about any missing disposition uploads from the past two weeks';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date')) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        $log = new AuditLog();
        $log->action = 'EMAIL:MISSING-DISPO-REPORT';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'date' => $date->format('Y-m-d'),
        ]);
        $log->save();

        SendMissingDispoReportJob::dispatch(
            date: $date,
            logId: $log->logId
        );

        return true;
    }
}
