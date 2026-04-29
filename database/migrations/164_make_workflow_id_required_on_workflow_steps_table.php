<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $nullCount = DB::table('workflow_steps')->whereNull('workflow_id')->count();

        if ($nullCount > 0) {
            throw new RuntimeException('Cannot make workflow_id required because some workflow_steps rows still have null workflow_id values.');
        }

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreignId('workflow_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('workflows')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreignId('workflow_id')
                ->nullable()
                ->change();
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('workflows')
                ->nullOnDelete();
        });
    }
};