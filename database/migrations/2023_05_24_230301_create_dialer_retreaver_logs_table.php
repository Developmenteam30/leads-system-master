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
        Schema::create('dialer_retreaver_logs', function (Blueprint $table) {
            $table->char('call_uuid', 255)->primary()->charset('latin1');
            $table->dateTime('time_stamp');
            $table->char('cid', 255)->nullable();
            $table->char('pub_id', 255)->nullable();
            $table->char('publisher_name', 255)->nullable();
            $table->char('sub_id', 255)->nullable();
            $table->char('number', 255)->nullable();
            $table->char('number_name', 255)->nullable();
            $table->mediumInteger('total_duration_secs')->nullable();
            $table->mediumInteger('ivr_duration_secs')->nullable();
            $table->mediumInteger('hold_duration_secs')->nullable();
            $table->mediumInteger('connected_secs')->nullable();
            $table->char('connected_to', 255)->nullable();
            $table->mediumInteger('billable_minutes')->nullable();
            $table->decimal('charged', 5)->nullable();
            $table->char('caller', 255)->nullable();
            $table->char('received_caller_id', 255)->nullable();
            $table->char('sent_caller_id', 255)->nullable();
            $table->char('caller_city', 255)->nullable();
            $table->char('caller_state', 255)->nullable();
            $table->char('caller_zip', 255)->nullable();
            $table->char('caller_country', 255)->nullable();
            $table->text('recording_url')->charset('latin1')->nullable();
            $table->mediumInteger('network_sale_timer_fired_sec')->nullable();
            $table->mediumInteger('affiliate_sale_timer_fired_sec')->nullable();
            $table->mediumInteger('target_sale_timer_fired_sec')->nullable();
            $table->char('fired_pixels', 255)->nullable();
            $table->char('postback_value', 255)->nullable();
            $table->decimal('revenue')->nullable();
            $table->decimal('payout')->nullable();
            $table->char('target_id', 255)->nullable();
            $table->char('target_name', 255)->nullable();
            $table->unsignedTinyInteger('converted')->nullable();
            $table->unsignedTinyInteger('duplicate')->nullable();
            $table->unsignedTinyInteger('repeat')->nullable();
            $table->char('status', 255)->nullable();
            $table->char('hung_up_by', 255)->nullable();
            $table->unsignedTinyInteger('receivable')->nullable();
            $table->unsignedTinyInteger('payable')->nullable();
            $table->char('session_notes', 255)->nullable();
            $table->char('visitor_url', 255)->nullable();
            $table->char('tag_list', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialer_retreaver_logs');
    }
};
