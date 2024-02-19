<?php

namespace App\Console\Commands\Reports;

use App\Jobs\Send90DayNotification;
use App\Models\AuditLog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class Send90DayNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:90-day-notification {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the list of agents who will hit their 90-day anniversary the following week';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date')) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

            $log = new AuditLog();
            $log->action = 'EMAIL:90-DAY-NOTIFICATION';
            $log->timestamp = Carbon::now();
            $log->notes = json_encode([
                'date' => $date->format('Y-m-d'),
            ]);
            $log->save();

            Send90DayNotification::dispatch(
                date: $date,
                logId: $log->logId,
            );

            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }
}
