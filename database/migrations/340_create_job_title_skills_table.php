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
        Schema::create('job_title_skills', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Job Title
            |--------------------------------------------------------------------------
            |
            | Each skill belongs to a Job Title.
            |
            */

            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Skill Information
            |--------------------------------------------------------------------------
            |
            | Examples:
            | - Laravel
            | - Vue.js
            | - Project Management
            | - SQL
            | - Risk Management
            |
            */

            $table->string('name');

            $table->text('description')
                ->nullable();

            $table->string('requirement_type', 20)->default('required');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Sort Order
            |--------------------------------------------------------------------------
            */

            $table->integer('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(['job_title_id', 'requirement_type']);

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Skills
            |--------------------------------------------------------------------------
            |
            | Prevents the same skill from being added twice to
            | the same Job Title.
            |
            */

            $table->unique([
                'job_title_id',
                'name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_title_skills');
    }
};