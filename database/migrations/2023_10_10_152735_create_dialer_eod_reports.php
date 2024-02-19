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
        Schema::create('dialer_eod_reports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('manager_agent_id')->references('id')->on('dialer_agents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('team_id')->references('id')->on('dialer_teams')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedInteger('team_count');
            $table->unsignedInteger('head_count');
            $table->text('attendance_notes');
            $table->text('early_leave');
            $table->unsignedInteger('day_prior_auto_fail');
            $table->unsignedInteger('day_prior_calls_under_89pct');
            $table->unsignedInteger('completed_evaluations');
            $table->unsignedInteger('agents_coached');
            $table->unsignedInteger('agents_on_pip');
            $table->text('notes');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_eod_reports');
    }
};
