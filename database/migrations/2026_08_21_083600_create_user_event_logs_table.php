<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('occurred_at')->index();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->string('event_type', 80);
            $table->string('module', 100)->nullable();
            $table->string('action', 100)->nullable();

            $table->string('route_name')->nullable();
            $table->json('route_parameters')->nullable();
            $table->string('path', 2048)->nullable();
            $table->string('http_method', 10)->nullable();

            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_identifier', 64)->nullable();
            $table->uuid('request_identifier')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['person_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['module', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('request_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_event_logs');
    }
};
