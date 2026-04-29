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
        Schema::create('workflow_step_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();

            $table->string('status_code');
            $table->string('status_label');

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['workflow_step_id', 'status_code']);
            $table->index(['workflow_step_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_step_statuses');
    }
};