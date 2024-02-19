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
        Schema::create('dialer_agent_pips_dialer_pip_reasons', function (Blueprint $table) {
            $table->unsignedBigInteger('pip_id');
            $table->unsignedBigInteger('reason_id');
            $table->primary(['pip_id', 'reason_id']);
            $table->foreign('pip_id')->references('id')->on('dialer_agent_pips')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('reason_id')->references('id')->on('dialer_pip_reasons')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_agent_pips_dialer_pip_reasons');
    }
};
