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
        Schema::table('dialer_access_areas', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_access_areas', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropUnique('dialer_access_areas_slug_unique');
        });
    }
};
