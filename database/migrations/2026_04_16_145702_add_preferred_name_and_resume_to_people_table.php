<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add preferred_name and resume_path to people table
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('preferred_name')
                ->nullable()
                ->after('first_name');

            $table->string('resume_path')
                ->nullable()
                ->after('notes');
        });
    }

    /**
     * Reverse the changes
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_name',
                'resume_path',
            ]);
        });
    }
};