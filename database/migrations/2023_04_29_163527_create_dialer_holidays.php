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
        if(!Schema::hasTable('dialer_holidays')){
            Schema::create('dialer_holidays', function (Blueprint $table) {
                $table->id();
                $table->date('holiday');
                $table->char('name', 255);
                $table->decimal('multiplier', $precision = 2, $scale = 1);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_holidays');
    }
};
