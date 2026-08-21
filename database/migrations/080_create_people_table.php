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
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->unique();

            $table->string('person_code')->nullable()->unique();

            $table->string('first_name');
            $table->string('alternate_first_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('last_name');
            $table->string('alternate_last_name')->nullable();

            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('employment_status')->nullable();

            $table->text('notes')->nullable();
            $table->string('resume_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};