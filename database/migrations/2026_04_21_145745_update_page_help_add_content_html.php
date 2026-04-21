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
        Schema::table('page_help', function (Blueprint $table) {

            // Add content_html if it does not exist
            if (!Schema::hasColumn('page_help', 'content_html')) {
                $table->longText('content_html')->nullable()->after('title');
            }

            // OPTIONAL: If you want to remove the old 'content' column later,
            // DO NOT do it yet unless you're sure nothing is using it.
            // if (Schema::hasColumn('page_help', 'content')) {
            //     $table->dropColumn('content');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_help', function (Blueprint $table) {

            // Remove content_html if rollback happens
            if (Schema::hasColumn('page_help', 'content_html')) {
                $table->dropColumn('content_html');
            }

            // NOTE: We do NOT recreate 'content' here because
            // we never removed it in this migration.
        });
    }
};