<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('navigation_label')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('page_type', 40)->default('standard')->index();
            $table->string('visibility', 32)->default('both');
            $table->string('status', 32)->default('draft');
            $table->string('menu_location', 32)->default('header');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('help_key')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'visibility', 'menu_location', 'sort_order']);
            $table->index(['effective_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
