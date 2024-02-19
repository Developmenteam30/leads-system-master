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
        Schema::create('dialer_agent_terminations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('agent_id')->references('id')->on('dialer_agents')->restrictOnDelete()->restrictOnUpdate();
            $table->date('sdr_report_date');
            $table->date('pip_issue_date');
            $table->date('term_approve_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('nominator_id')->references('id')->on('dialer_agents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('reason_id')->references('id')->on('dialer_agent_termination_reasons')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_agent_terminations');
    }
};
