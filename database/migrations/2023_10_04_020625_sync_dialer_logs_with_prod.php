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
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->char('income', 255)->nullable();
            $table->char('source_name', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_logs', function (Blueprint $table) {
            $table->dropColumn('income');
            $table->dropColumn('source_name');
        });
    }
};
