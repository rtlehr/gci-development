<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();

            $table->string('name');
            $table->string('full_path')->nullable();
            $table->string('path_ids')->nullable();
            $table->unsignedInteger('depth')->default(0);

            $table->string('status')->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['parent_id', 'name']);
            $table->index('full_path');
            $table->index('path_ids');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};