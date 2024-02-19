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
            $table->dropForeign(['leave_request_reason_id']);
            $table->dropColumn('leave_request_reason_id');
        });

        Schema::dropIfExists('dialer_leave_request_reasons');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('dialer_leave_request_reasons', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('name');
        });

        Schema::table('dialer_leave_requests', function (Blueprint $table) {
            $table->addColumn('leave_request_reason_id', 'unsignedBigInteger');
            $table->foreign('leave_request_reason_id')->references('id')->on('dialer_leave_request_reasons')->restrictOnDelete()->restrictOnUpdate();
        });
    }
};
