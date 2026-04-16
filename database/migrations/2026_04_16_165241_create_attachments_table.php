<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');

            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index('category');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};