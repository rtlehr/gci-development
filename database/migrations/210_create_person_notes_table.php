<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnDelete();
            $table->foreignId('entered_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('entered_by_name');
            $table->string('category', 20);
            $table->text('note');
            $table->timestamps();

            $table->index(['person_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_notes');
    }
};
