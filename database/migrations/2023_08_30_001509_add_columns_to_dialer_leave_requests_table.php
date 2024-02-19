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
            $table->unsignedBigInteger('leave_request_reason_id');
            $table->unsignedBigInteger('leave_request_type_id');
            $table->foreign('leave_request_reason_id')->references('id')->on('dialer_leave_request_reasons')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('leave_request_type_id')->references('id')->on('dialer_leave_request_types')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->dropColumn('leave_request_reason_id');
            $table->dropColumn('leave_request_type_id');
        });
    }
};
