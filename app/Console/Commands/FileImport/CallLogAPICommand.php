<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\ProcessCallLogAPIJob;
use App\Models\AuditLog;
use App\Models\DialerHoliday;
use App\Models\DialerHolidayList;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CallLogAPICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:call-log-api {date?} {--sendEmail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import call log from Convoso API';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date'), new \DateTimeZone(config('settings.timezone.local'))) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        // Skip US holidays
        $holiday = DialerHoliday::query()
            ->whereDate('holiday', $date)
            ->whereHas('holidayLists', function (Builder $query) {
                $query->where('holiday_list_id', DialerHolidayList::US_ID);
            })
            ->exists();

        if ($holiday) {
            Log::info(self::class.' Skipping due to holiday');
        } else {
            $log = new AuditLog();
            $log->action = ProcessCallLogAPIJob::ACTION_NAME;
            $log->timestamp = Carbon::now();
            $log->notes = json_encode([
                'file_date' => $date->format('Y-m-d'),
            ]);
            $log->save();

            ProcessCallLogAPIJob::dispatch(
                date: $date,
                logId: $log->logId,
                sendEmail: $this->option('sendEmail'),
            );
        }

        return true;
    }
}
