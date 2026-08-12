<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->boolean('is_list_column')->default(false)->after('is_active');
            $table->boolean('is_searchable')->default(false)->after('is_list_column');
            $table->boolean('is_filterable')->default(false)->after('is_searchable');
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn(['is_list_column', 'is_searchable', 'is_filterable']);
        });
    }
};
