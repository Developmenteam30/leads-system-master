<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\ProcessAgentPerformanceImport;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AgentPerformanceAPICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:agent-performance-api {date?} {email?} {--sendStatsReport}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agent performance from Convoso API';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? Carbon::parse($this->argument('date')) : Carbon::now(new \DateTimeZone(config('settings.timezone.local')))->timezone('UTC')->subDay();
        $email = !empty($this->argument('email')) ? $this->argument('email') : config('settings.default_job_email');
        $sendStatsReport = $this->option('sendStatsReport');

        $log = new AuditLog();
        $log->action = 'UPLOAD:DIALER-AGENT-PERFORMANCE';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'file' => 'API Import: '.$date->format('Y-m-d'),
            'file_date' => $date->format('Y-m-d'),
        ]);
        $log->save();

        ProcessAgentPerformanceImport::dispatch(
            date: $date,
            email: $email,
            logId: $log->logId,
            sendStatsReport: $sendStatsReport,
        );

        return true;
    }
}
