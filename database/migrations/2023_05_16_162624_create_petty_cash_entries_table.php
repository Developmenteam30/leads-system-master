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
        if(!Schema::hasTable('dialer_petty_cash_entries')){
            Schema::create('dialer_petty_cash_entries', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('agent_id');
                $table->unsignedBigInteger('petty_cash_reason_id');
                $table->date('date');
                $table->text('notes')->nullable();
                $table->enum('type', ['in', 'out'])->default('out');
                $table->decimal('amount', $precision = 8, $scale = 2);
                $table->foreign('agent_id')->references('id')->on('dialer_agents')->restrictOnDelete()->restrictOnUpdate();
                $table->foreign('petty_cash_reason_id')->references('id')->on('dialer_petty_cash_reasons')->restrictOnDelete()->restrictOnUpdate();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_petty_cash_entries');
    }
};
