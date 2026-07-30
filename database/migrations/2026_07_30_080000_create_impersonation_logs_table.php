<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('impersonator_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('impersonated_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('session_identifier')->unique();
            $table->text('reason')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('termination_reason', 100)->nullable();
            $table->timestamps();

            $table->index(['impersonator_user_id', 'started_at']);
            $table->index(['impersonated_user_id', 'started_at']);
            $table->index(['ended_at', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
