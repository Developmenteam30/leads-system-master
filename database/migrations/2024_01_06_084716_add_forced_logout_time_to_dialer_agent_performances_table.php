<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dialer_agent_performances', function (Blueprint $table) {
            $table->mediumInteger('forced_logout_cnt')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_agent_performances', function (Blueprint $table) {
            $table->dropColumn('forced_logout_cnt');
        });
    }
};
