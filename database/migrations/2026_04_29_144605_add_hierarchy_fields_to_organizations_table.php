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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('full_path')->nullable()->after('name');
            $table->string('path_ids')->nullable()->after('full_path');
            $table->unsignedInteger('depth')->default(0)->after('path_ids');

            $table->index('full_path');
            $table->index('path_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['full_path']);
            $table->dropIndex(['path_ids']);

            $table->dropColumn([
                'full_path',
                'path_ids',
                'depth',
            ]);
        });
    }
    
};
