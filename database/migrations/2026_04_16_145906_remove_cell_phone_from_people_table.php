<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove cell_phone column from people table
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('cell_phone');
        });
    }

    /**
     * Restore cell_phone column if rolled back
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('cell_phone')->nullable()->after('company_name');
        });
    }
};