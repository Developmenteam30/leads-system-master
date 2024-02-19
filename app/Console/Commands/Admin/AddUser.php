<?php

namespace App\Console\Commands\Admin;

use App\Models\DialerAccessRole;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     *
     */
    protected $signature = 'admin:adduser {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a user with developer to the system';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();

        $agent = new DialerAgent();
        $agent->id = DialerAgent::query()
            ->select(DB::raw('MAX(id)+1 AS agent_id'))
            ->where('id', 'LIKE', '9___')
            ->pluck('agent_id')
            ->first();
        $agent->password = Hash::make($this->argument('password'));
        $agent->agent_name = $this->argument('name');
        $agent->email = $this->argument('email');
        $agent->access_role_id = DialerAccessRole::ACCESS_ROLE_DEVELOPER;
        $agent->save();

        $effectiveDate = DialerAgentEffectiveDate::createDefaultEntry($agent, now(config('settings.timezone.local'))->format('Y-m-d'));
        $effectiveDate->agent_type_id = DialerAgentType::USER;
        $effectiveDate->save();

        DB::commit();

        return true;
    }
}
