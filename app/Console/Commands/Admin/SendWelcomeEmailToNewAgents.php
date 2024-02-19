<?php

namespace App\Console\Commands\Admin;

use App\Http\Controllers\Api\ForgotController;
use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendWelcomeEmailToNewAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:send-welcome-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send welcome emails to all agents on the Monday following their start date.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 8143: Send welcome emails to all agents on the Monday following their start date.
        $today = CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        $agents = DialerAgent::query()
            ->select([
                DB::raw('dialer_agents.id AS id'),
                'dialer_agents.agent_name',
                'dialer_agents.email',
            ])
            ->whereBetween('dialer_agent_effective_dates.start_date', [
                $today->subWeek()->startOfWeek(),
                $today->subWeek()->endOfWeek(),
            ])
            ->whereNotNull('dialer_agents.email')
            ->isActiveForDate($today->format('Y-m-d'))
            ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
            ->get();

        foreach ($agents as $agent) {
            print "{$agent->id} {$agent->agent_name}\r\n";

            try {
                ForgotController::generateForgotToken(
                    agent: $agent,
                    welcome_message: true,
                );
            } catch (\Throwable $e) {
                print $e->getMessage();
            }
        }

        return true;
    }
}
