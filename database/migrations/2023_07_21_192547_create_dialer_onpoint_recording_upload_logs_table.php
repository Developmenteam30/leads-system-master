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
        Schema::create('dialer_onpoint_recording_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('log_id');
            $table->unsignedInteger('call_id');
            $table->unsignedBigInteger('onpoint_id')->nullable();
            $table->char('status', 255)->nullable();
            $table->foreign('log_id')->references('logId')->on('auditlog')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('call_id')->references('call_id')->on('dialer_logs')->restrictOnDelete()->restrictOnUpdate();
            $table->index('log_id');
            $table->index('call_id');
            $table->index('onpoint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_onpoint_recording_upload_logs');
    }
};
