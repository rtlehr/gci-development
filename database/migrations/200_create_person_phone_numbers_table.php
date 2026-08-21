<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create person_phone_numbers table
     */
    public function up(): void
    {
        Schema::create('person_phone_numbers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnDelete();

            $table->string('phone_number', 50);
            $table->string('phone_type', 50)->nullable(); // mobile, work, home, fax, other
            $table->boolean('is_primary')->default(false);
            $table->text('extension')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('person_id');
            $table->index('phone_type');
            $table->index('is_primary');
        });
    }

    /**
     * Drop person_phone_numbers table
     */
    public function down(): void
    {
        Schema::dropIfExists('person_phone_numbers');
    }
};