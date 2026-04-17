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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            // resume_review, interview, tech_screen, offer_sent, offer_signed, etc.

            $table->string('name');
            $table->unsignedInteger('step_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->boolean('allows_requested_at')->default(false);
            $table->boolean('allows_scheduled_at')->default(false);
            $table->boolean('allows_completed_at')->default(false);
            $table->boolean('allows_notes')->default(false);
            $table->boolean('allows_comments')->default(false);
            $table->boolean('allows_status')->default(false);

            $table->string('default_status')->nullable();

            $table->timestamps();

            $table->index('step_order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};