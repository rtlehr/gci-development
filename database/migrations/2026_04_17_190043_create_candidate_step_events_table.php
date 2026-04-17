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
        Schema::create('candidate_step_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();

            $table->string('status_code')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('performed_by_person_id')->nullable()->constrained('people')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->text('comments')->nullable();

            $table->json('metadata')->nullable();
            // future-safe for special step-specific data

            $table->timestamps();

            $table->index('candidate_id');
            $table->index('workflow_step_id');
            $table->index('status_code');
            $table->index('scheduled_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_step_events');
    }
};