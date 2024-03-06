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
        Schema::table('dialer_campaign_defaults', function (Blueprint $table) {
            $table->enum('bonus_type', ['tier_based', 'flat_rate'])->default('flat_rate');
            $table->longText('tier_bonus_rates')->nullable();
            $table->decimal('billable_training_rate', 10, 2)->default(0);
            $table->decimal('payable_training_rate', 10, 2)->default(0);
            $table->integer('training_duration')->default(10);
            $table->longText('special_billing_rates')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_campaign_defaults', function (Blueprint $table) {
            $table->dropColumn('bonus_type');
            $table->dropColumn('tier_bonus_rates');
            $table->dropColumn('billable_training_rate');
            $table->dropColumn('payable_training_rate');
            $table->dropColumn('training_duration');
            $table->dropColumn('special_billing_rates');
        });
    }
};
