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
        Schema::table('dialer_retreaver_logs', function (Blueprint $table) {
            $table->dropColumn('network_sale_timer_fired_sec');
            $table->dropColumn('affiliate_sale_timer_fired_sec');
            $table->dropColumn('target_sale_timer_fired_sec');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_retreaver_logs', function (Blueprint $table) {
            $table->mediumInteger('network_sale_timer_fired_sec')->nullable();
            $table->mediumInteger('affiliate_sale_timer_fired_sec')->nullable();
            $table->mediumInteger('target_sale_timer_fired_sec')->nullable();
        });
    }
};
