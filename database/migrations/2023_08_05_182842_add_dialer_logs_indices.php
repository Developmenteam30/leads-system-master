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
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->index(['agent_id', 'time_stamp']);
            $table->index(['time_stamp', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->dropIndex(['agent_id', 'time_stamp']);
            $table->dropIndex(['time_stamp', 'year']);
        });
    }
};
