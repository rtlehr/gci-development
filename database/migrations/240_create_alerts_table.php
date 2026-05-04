<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            // Who receives the alert
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Optional: tie to person if your app uses people heavily
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();

            $table->string('type')->default('general');
            $table->string('priority')->default('normal'); // low, normal, high

            $table->string('title');
            $table->text('message')->nullable();

            // Optional link the user clicks
            $table->string('action_url')->nullable();

            // Optional source reference
            $table->string('source_type')->nullable(); // ticket, candidate_workflow, assignment, etc.
            $table->unsignedBigInteger('source_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('emailed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};