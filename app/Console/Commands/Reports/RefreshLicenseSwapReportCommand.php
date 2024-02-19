<?php

namespace App\Console\Commands\Reports;

use App\Jobs\RefreshLicenseSwapReportJob;
use App\Models\AuditLog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RefreshLicenseSwapReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:license-swap {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh and send the license swap report';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date')) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        $log = new AuditLog();
        $log->action = 'EMAIL:LICENSE-SWAP-REPORT';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'date' => $date->format('Y-m-d'),
        ]);
        $log->save();

        RefreshLicenseSwapReportJob::dispatch(
            date: $date,
            logId: $log->logId,
        );

        return true;
    }
}
