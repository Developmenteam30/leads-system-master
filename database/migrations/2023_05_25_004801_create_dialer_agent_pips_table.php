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
        Schema::create('dialer_agent_pips', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('agent_id')->constrained( table: 'dialer_agents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('reporter_agent_id')->constrained( table: 'dialer_agents')->restrictOnDelete()->restrictOnUpdate();
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_agent_pips');
    }
};
