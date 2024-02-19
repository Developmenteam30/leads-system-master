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
            $table->unsignedBigInteger('internal_campaign_id')->nullable();
            $table->foreign('internal_campaign_id')->references('id')->on('dialer_products')->restrictOnDelete()->restrictOnUpdate();
            $table->dropUnique('dialer_agent_performance_UN');
            $table->unique(['agent_id', 'file_date', 'internal_campaign_id'], 'dialer_agent_performances_agent_date_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_agent_performances', function (Blueprint $table) {
            $table->dropColumn('internal_campaign_id');
        });
    }
};
