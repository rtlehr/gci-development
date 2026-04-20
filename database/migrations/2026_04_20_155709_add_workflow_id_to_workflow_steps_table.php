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
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreignId('workflow_id')
                ->nullable()
                ->after('id')
                ->constrained('workflows')
                ->nullOnDelete();

            $table->index(['workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropIndex(['workflow_id', 'step_order']);
            $table->dropColumn('workflow_id');
        });
    }
};