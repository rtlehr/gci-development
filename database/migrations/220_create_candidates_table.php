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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();

            $table->string('candidate_code')->nullable()->unique();

            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnDelete();

            $table->foreignId('position_id')
                ->constrained('positions')
                ->cascadeOnDelete();

            $table->foreignId('workflow_id')
                ->nullable()
                ->constrained('workflows')
                ->nullOnDelete();

            $table->string('status')->default('submitted');
            // submitted, selected, approved, assigned, rejected, closed, etc.

            $table->decimal('candidate_fbr', 10, 2)->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('submitted_by_person_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();

            $table->date('scheduled_start_date')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('workflow_id');
            $table->index(['person_id', 'position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};