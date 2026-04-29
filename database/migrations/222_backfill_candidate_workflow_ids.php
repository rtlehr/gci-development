<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        //
    }
};