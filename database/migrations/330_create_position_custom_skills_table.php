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
        Schema::create('position_custom_skills', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Position
            |--------------------------------------------------------------------------
            |
            | These are skills that are unique to a specific Position and
            | supplement the default skills inherited from the Job Title.
            |
            */

            $table->foreignId('position_id')
                ->constrained('positions')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Skill Information
            |--------------------------------------------------------------------------
            |
            | Examples:
            | - Active Secret Clearance
            | - Experience with IRAD
            | - Knowledge of GCI Processes
            | - Contract Transition Experience
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
            | Prevent Duplicate Skills
            |--------------------------------------------------------------------------
            |
            | Prevents duplicate custom skills on the same Position.
            |
            */

            $table->unique([
                'position_id',
                'name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_custom_skills');
    }
};