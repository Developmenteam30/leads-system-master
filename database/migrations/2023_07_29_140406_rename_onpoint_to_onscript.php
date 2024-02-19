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
        Schema::table('dialer_onpoint_recording_upload_logs', function (Blueprint $table) {
            $table->renameColumn('onpoint_id', 'onscript_id');
            $table->rename('dialer_onscript_recording_upload_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_onscript_recording_upload_logs', function (Blueprint $table) {
            $table->renameColumn('onscript_id', 'onpoint_id');
            $table->rename('dialer_onpoint_recording_upload_logs');
        });
    }
};
