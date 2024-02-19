<?php

namespace App\Console;

use App\Console\Commands\Admin\SendWelcomeEmailToNewAgents;
use App\Console\Commands\FileImport\AgentPerformanceAPICommand;
use App\Console\Commands\FileImport\CallLogAPICommand;
use App\Console\Commands\FileImport\ImportRetreaverFilesCommand;
use App\Console\Commands\Reports\RefreshLicenseSwapReportCommand;
use App\Console\Commands\Reports\Send90DayNotificationCommand;
use App\Console\Commands\Reports\SendDailyProgressEmailCommand;
use App\Console\Commands\Reports\SendLicensingReportCommand;
use App\Console\Commands\Reports\SendMissingDispoReportCommand;
use App\Jobs\SendLicensingReport;
use App\Jobs\SendMissingDispoReportJob;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        //
        // NOTE: ALL TIMES ARE IN BELIZE TIMEZONE (GMT-6)
        // During ET standard time, NY is 1 hour ahead of Belize.
        // During ET daylight savings time, NY is 2 hours ahead of Belize.
        //

        $schedule->command(AgentPerformanceAPICommand::class, ['--sendStatsReport'])->dailyAt("05:00");
        $schedule->command(SendLicensingReportCommand::class)->dailyAt(SendLicensingReport::REPORT_HOUR.":".SendLicensingReport::REPORT_MINUTE);
        $schedule->command(SendMissingDispoReportCommand::class)->dailyAt(SendMissingDispoReportJob::REPORT_HOUR.":".SendMissingDispoReportJob::REPORT_MINUTE);
        $schedule->command(ImportRetreaverFilesCommand::class)->dailyAt("10:00");

        $schedule->command(SendDailyProgressEmailCommand::class)->cron("00 21 * * 1-5");
        $schedule->command(RefreshLicenseSwapReportCommand::class)->cron("30 08 * * 1-5");

        // 8143: Send welcome emails to all agents on the Monday following their start date, starting on 2024-01-15.
        $schedule->command(SendWelcomeEmailToNewAgents::class)->cron("00 08 * * 1")->when(function () {
            return CarbonImmutable::now()->setTimezone(config('settings.timezone.belize'))->gte('2024-01-15');
        });;

        // 7949: Send the License Swap report on the weekends through Dec 8, 2023
        $schedule->command(RefreshLicenseSwapReportCommand::class)->cron("30 09 * * 6")->when(function () {
            return CarbonImmutable::now()->setTimezone(config('settings.timezone.belize'))->lte('2023-12-08');
        });
        $schedule->command(RefreshLicenseSwapReportCommand::class)->cron("30 10 * * 0")->when(function () {
            return CarbonImmutable::now()->setTimezone(config('settings.timezone.belize'))->lte('2023-12-08');
        });

        $schedule->command(CallLogAPICommand::class)->cron("0 8-17 * * 1-5");
        $schedule->command(CallLogAPICommand::class, ['--sendEmail'])->cron("30 8 * * 1-5");
        $schedule->command(CallLogAPICommand::class, ['--sendEmail'])->cron("30 11 * * 1-5");
        $schedule->command(CallLogAPICommand::class, ['--sendEmail'])->cron("30 16 * * 1-5");

        $schedule->command(Send90DayNotificationCommand::class)->cron("0 8 * * 4");
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        return config('settings.timezone.belize');
    }
}
