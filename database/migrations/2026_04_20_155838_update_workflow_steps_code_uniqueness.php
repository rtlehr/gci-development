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
            $table->dropUnique('workflow_steps_code_unique');
            $table->unique(['workflow_id', 'code'], 'workflow_steps_workflow_id_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropUnique('workflow_steps_workflow_id_code_unique');
            $table->unique('code', 'workflow_steps_code_unique');
        });
    }
};