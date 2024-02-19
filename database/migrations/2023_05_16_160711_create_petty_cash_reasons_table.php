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
        if (!Schema::hasTable('dialer_petty_cash_reasons')) {
            Schema::create('dialer_petty_cash_reasons', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->char('reason', 255)->unique();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_petty_cash_reasons');
    }
};
