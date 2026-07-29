<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->string('page_type', 40)
                ->default('standard')
                ->after('content_html')
                ->index();
        });

        Schema::create('content_page_faq_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_page_id')
                ->constrained('content_pages')
                ->cascadeOnDelete();
            $table->string('question');
            $table->longText('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index(
                ['content_page_id', 'is_active', 'sort_order'],
                'content_page_faq_active_sort_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_page_faq_items');

        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropIndex(['page_type']);
            $table->dropColumn('page_type');
        });
    }
};
