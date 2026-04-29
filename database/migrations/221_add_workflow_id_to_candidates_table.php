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
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('workflow_id')
                ->nullable()
                ->after('position_id')
                ->constrained('workflows')
                ->nullOnDelete();

            $table->index('workflow_id');
        });

        $primaryWorkflowId = DB::table('workflows')
            ->where('is_primary', true)
            ->value('id');

        if ($primaryWorkflowId) {
            DB::table('candidates')
                ->whereNull('workflow_id')
                ->update([
                    'workflow_id' => $primaryWorkflowId,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropIndex(['workflow_id']);
            $table->dropColumn('workflow_id');
        });
    }
};