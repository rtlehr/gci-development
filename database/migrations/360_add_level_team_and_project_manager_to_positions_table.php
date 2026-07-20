<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->nullable()->after('experience_level');
            $table->string('team_name')->nullable()->after('project_team_name');
            $table->foreignId('project_manager_user_id')
                ->nullable()
                ->after('team_name')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('positions')
            ->whereNull('level')
            ->update([
                'level' => DB::raw("CASE experience_level
                    WHEN 'Beginner' THEN 1
                    WHEN 'Novice' THEN 2
                    WHEN 'Experienced' THEN 3
                    WHEN 'Senior' THEN 4
                    ELSE NULL
                END"),
            ]);

        DB::table('positions')
            ->whereNull('team_name')
            ->whereNotNull('project_team_name')
            ->update(['team_name' => DB::raw('project_team_name')]);
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_manager_user_id');
            $table->dropColumn(['team_name', 'level']);
        });
    }
};
