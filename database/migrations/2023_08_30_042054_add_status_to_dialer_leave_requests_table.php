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
        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->unsignedBigInteger('leave_request_status_id');
            $table->foreign('leave_request_status_id')->references('id')->on('dialer_leave_request_statuses')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('reviewer_agent_id')->nullable();
            $table->foreign('reviewer_agent_id')->references('id')->on('dialer_agents')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->dropColumn('leave_request_status_id');
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
        });
    }
};
