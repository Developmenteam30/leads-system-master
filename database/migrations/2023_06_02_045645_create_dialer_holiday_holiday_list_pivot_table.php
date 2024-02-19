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
        Schema::create('dialer_holidays_holiday_lists', function (Blueprint $table) {
            $table->unsignedBigInteger("holiday_id");
            $table->unsignedBigInteger("holiday_list_id");
            $table->primary(['holiday_id', 'holiday_list_id']);
            $table->foreign('holiday_id')->references('id')->on('dialer_holidays')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('holiday_list_id')->references('id')->on('dialer_holiday_lists')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_holidays_holiday_lists');
    }
};
