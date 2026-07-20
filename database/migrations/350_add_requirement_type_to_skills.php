<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_title_skills', function (Blueprint $table) {
            $table->string('requirement_type', 20)->default('required')->after('description');
            $table->index(['job_title_id', 'requirement_type']);
        });

        Schema::table('position_custom_skills', function (Blueprint $table) {
            $table->string('requirement_type', 20)->default('required')->after('description');
            $table->index(['position_id', 'requirement_type']);
        });
    }

    public function down(): void
    {
        Schema::table('position_custom_skills', function (Blueprint $table) {
            $table->dropIndex(['position_id', 'requirement_type']);
            $table->dropColumn('requirement_type');
        });

        Schema::table('job_title_skills', function (Blueprint $table) {
            $table->dropIndex(['job_title_id', 'requirement_type']);
            $table->dropColumn('requirement_type');
        });
    }
};
