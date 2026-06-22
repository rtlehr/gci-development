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
        Schema::create('job_title_tasks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Job Title
            |--------------------------------------------------------------------------
            |
            | Each task belongs to a Job Title.
            |
            */

            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Task Information
            |--------------------------------------------------------------------------
            |
            | Examples:
            | - Develop frontend features
            | - Conduct code reviews
            | - Create project documentation
            | - Support production deployments
            | - Manage project schedules
            |
            */

            $table->string('name');

            $table->text('description')
                ->nullable();

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

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Tasks
            |--------------------------------------------------------------------------
            |
            | Prevents the same task from being added twice
            | to the same Job Title.
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
        Schema::dropIfExists('job_title_tasks');
    }
};