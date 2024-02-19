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
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->change();
            $table->foreign('campaign_id')->references('id')->on('dialer_external_campaigns')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->dropForeign('');
        });
    }
};
