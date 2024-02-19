<?php

namespace App\Console\Commands\Reports;

use App\Jobs\SendLicensingReport;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLicensingReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:licensing-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the report of licenses to be terminated that day';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? Carbon::parse($this->argument('date')) : Carbon::now(new \DateTimeZone(config('settings.timezone.local')));

        $log = new AuditLog();
        $log->action = 'EMAIL:LICENSE-REPORT';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'date' => $date->format('Y-m-d'),
        ]);
        $log->save();

        SendLicensingReport::dispatch($date, $log->logId);

        return true;
    }
}
