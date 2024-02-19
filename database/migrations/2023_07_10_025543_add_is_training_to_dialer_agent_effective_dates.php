<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dialer_agent_effective_dates', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_training')->default(0);
        });

        $agents = \App\Models\DialerAgent::get();
        foreach ($agents as $agent) {
            $effective_date = \App\Models\DialerAgentEffectiveDate::query()
                ->where('agent_id', $agent->id)
                ->where('start_date', $agent->start_date)
                ->first();

            if ($effective_date) {
                $effective_date->is_training = 1;
                $effective_date->save();
            }
        }

        Schema::table('dialer_agents', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_agent_effective_dates', function (Blueprint $table) {
            $table->dropColumn('is_training');
        });

        Schema::table('dialer_agents', function (Blueprint $table) {
            $table->date('start_date');
        });
    }
};
