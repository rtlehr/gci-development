<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnDelete();

            $table->string('address_type', 50)->nullable(); // home, work, mailing, other
            $table->string('line_1');
            $table->string('line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable()->default('USA');
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('person_id');
            $table->index('address_type');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_addresses');
    }
};