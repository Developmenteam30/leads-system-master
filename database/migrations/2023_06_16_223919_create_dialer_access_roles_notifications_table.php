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
        Schema::create('dialer_access_roles_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("notification_type_id");
            $table->primary(['role_id', 'notification_type_id']);
            $table->foreign('role_id')->references('id')->on('dialer_access_roles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('notification_type_id')->references('id')->on('dialer_notification_types')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_access_roles_notifications');
    }
};
