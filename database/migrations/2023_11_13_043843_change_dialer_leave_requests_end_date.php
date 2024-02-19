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
        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->renameColumn('end_date', 'end_time');
        });

        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->dateTime('end_time')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->date('end_time')->change();
            $table->renameColumn('end_time', 'end_date');
        });
    }
};
