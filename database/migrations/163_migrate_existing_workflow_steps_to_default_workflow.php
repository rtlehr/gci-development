<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $existingWorkflow = DB::table('workflows')
            ->where('code', 'default_candidate_workflow')
            ->first();

        if (!$existingWorkflow) {
            $workflowId = DB::table('workflows')->insertGetId([
                'name' => 'Default Candidate Workflow',
                'code' => 'default_candidate_workflow',
                'description' => 'Migrated default workflow from existing workflow steps.',
                'is_active' => true,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $workflowId = $existingWorkflow->id;
        }

        DB::table('workflow_steps')
            ->whereNull('workflow_id')
            ->update([
                'workflow_id' => $workflowId,
                'updated_at' => $now,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workflow_steps')
            ->whereNotNull('workflow_id')
            ->update([
                'workflow_id' => null,
                'updated_at' => now(),
            ]);

        DB::table('workflows')
            ->where('code', 'default_candidate_workflow')
            ->delete();
    }
};