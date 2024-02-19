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
        Schema::table('dialer_petty_cash_locations', function (Blueprint $table) {
            $table->softDeletes();
            $table->unique('location');
        });
        Schema::table('dialer_petty_cash_notes', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('dialer_petty_cash_vendors', function (Blueprint $table) {
            $table->softDeletes();
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_petty_cash_locations', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique('location');
        });
        Schema::table('dialer_petty_cash_notes', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('dialer_petty_cash_vendors', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropUnique('vendor');
        });
    }
};
