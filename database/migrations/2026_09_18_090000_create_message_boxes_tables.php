<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->longText('content_html')->nullable();
            $table->enum('display_type', ['modal', 'top_banner', 'bottom_banner'])->default('modal');
            $table->enum('audience', ['everyone', 'roles', 'permissions'])->default('everyone');
            $table->json('audience_values')->nullable();
            $table->json('page_patterns')->nullable();
            $table->enum('trigger_type', ['automatic', 'manual'])->default('automatic');
            $table->string('trigger_key')->nullable()->index();
            $table->boolean('show_once')->default(false);
            $table->boolean('dismissible')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->json('actions')->nullable();
            $table->json('form_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('message_box_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['message_box_id', 'user_id']);
        });

        Schema::create('message_box_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_box_submissions');
        Schema::dropIfExists('message_box_user_states');
        Schema::dropIfExists('message_boxes');
    }
};
