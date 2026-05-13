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

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            // User receiving the alert
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional linked person record
            $table->foreignId('person_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Alert Details
            |--------------------------------------------------------------------------
            */

            // General alert category
            // Examples:
            // ticket_assignment
            // workflow
            // assignment
            // reminder
            // system
            $table->string('type')
                ->default('general');

            // low, normal, high
            $table->string('priority')
                ->default('normal');

            $table->string('title');

            $table->text('message')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Navigation / Source Tracking
            |--------------------------------------------------------------------------
            */

            // URL the user can click to view the related item
            $table->string('action_url')
                ->nullable();

            // Related source type
            // Examples:
            // ticket
            // candidate
            // workflow
            // assignment
            $table->string('source_type')
                ->nullable();

            // Related source record ID
            $table->unsignedBigInteger('source_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional Metadata
            |--------------------------------------------------------------------------
            */

            // Flexible JSON storage for future expansion
            $table->json('metadata')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Read Tracking
            |--------------------------------------------------------------------------
            */

            // When the user viewed/read the alert
            $table->timestamp('read_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Email Notification Tracking
            |--------------------------------------------------------------------------
            */

            // Whether this alert should generate an email
            $table->boolean('should_email')
                ->default(false);

            // When the email job/process started
            $table->timestamp('email_queued_at')
                ->nullable();

            // When the email was successfully sent
            $table->timestamp('emailed_at')
                ->nullable();

            // Store any send failure message
            $table->text('email_error')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(['user_id', 'read_at']);

            $table->index([
                'user_id',
                'should_email',
                'emailed_at',
            ]);

            $table->index([
                'source_type',
                'source_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};