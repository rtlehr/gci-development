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
        Schema::create('positions', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Core Position Information
            |--------------------------------------------------------------------------
            */

            $table->string('position_code')->unique();

            $table->enum('status', [
                'Open',
                'In Process',
                'Closed',
            ])->default('Open');

            $table->string('job_title');

            $table->foreignId('job_title_id')
                ->nullable()
                ->constrained('job_titles')
                ->nullOnDelete();

            $table->enum('experience_level', [
                'Beginner',
                'Novice',
                'Experienced',
                'Senior',
            ])->nullable();

            $table->unsignedTinyInteger('level')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Labor Category
            |--------------------------------------------------------------------------
            |
            | Auto-generated from:
            | Job Title + " - " + Experience Level
            |
            | Example:
            | Software Developer - Senior
            |--------------------------------------------------------------------------
            */

            $table->string('labor_category')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Requirements / Qualifications
            |--------------------------------------------------------------------------
            */

            $table->text('certifications_required')->nullable();

            $table->text('training_required')->nullable();

            $table->text('experience')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Flags
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_essential')
                ->default(false);

            $table->boolean('travel_required')
                ->default(false);

            $table->boolean('high_risk_role')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Location Information
            |--------------------------------------------------------------------------
            */

            $table->string('location')->nullable();

            $table->string('building')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Mission / Component
            |--------------------------------------------------------------------------
            */

            $table->text('mission_description')->nullable();

            $table->string('component')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Organizations
            |--------------------------------------------------------------------------
            */

            $table->foreignId('position_organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();

            $table->foreignId('sponsoring_organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();

            $table->foreignId('funding_organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Funding
            |--------------------------------------------------------------------------
            */

            $table->longText('funding_info')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Closure Workflow
            |--------------------------------------------------------------------------
            */

            $table->boolean('request_to_close')
                ->default(false);

            $table->date('scheduled_to_close')
                ->nullable();

            $table->date('close_date')
                ->nullable();

            $table->longText('close_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Legacy / Existing Fields
            |--------------------------------------------------------------------------
            */

            $table->string('project_team_name')
                ->nullable();

            $table->string('team_name')->nullable();

            $table->foreignId('project_manager_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('customer_lead_name')
                ->nullable();

            $table->date('customer_created_at')
                ->nullable();

            $table->longText('notes')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};