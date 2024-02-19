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
        Schema::table('dialer_agent_performances', function (Blueprint $table) {
            $table->unsignedMediumInteger('huddle_time')->nullable();
            $table->unsignedMediumInteger('coaching_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_agent_performances', function (Blueprint $table) {
            $table->dropColumn('huddle_time');
            $table->dropColumn('coaching_time');
        });
    }
};
