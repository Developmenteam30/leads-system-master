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
        Schema::table('dialer_documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->references('id')->on('dialer_document_types')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_documents', function (Blueprint $table) {
            $table->dropColumn('document_type_id');
        });
    }
};
