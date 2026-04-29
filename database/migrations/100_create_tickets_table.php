<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number')->unique();

            $table->string('title');
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('request_type');   // bug, improvement
            $table->string('importance');     // show_stopper, asap, nice_to_have
            $table->string('category')->nullable(); // UI, Data, Permissions, Workflow, Other

            $table->text('description');
            $table->text('source_url')->nullable();

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('new'); // new, in_progress, on_hold, complete, canceled
            $table->text('resolution_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};