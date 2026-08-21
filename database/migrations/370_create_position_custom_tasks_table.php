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
        Schema::create('position_custom_tasks', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Parent Position
            |--------------------------------------------------------------------------
            |
            | These are tasks that are unique to a specific Position and
            | supplement the default tasks inherited from the Job Title.
            |
            */

            $table->foreignId('position_id')
                ->constrained('positions')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Task Information
            |--------------------------------------------------------------------------
            |
            | Examples:
            | - Support IRAD modernization effort
            | - Coordinate transition activities
            | - Maintain contract deliverables
            | - Support customer demonstrations
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
            | Prevents duplicate custom tasks on the same Position.
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
        Schema::dropIfExists('position_custom_tasks');
    }
};