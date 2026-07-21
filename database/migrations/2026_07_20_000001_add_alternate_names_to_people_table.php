<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('alternate_first_name')
                ->nullable()
                ->after('first_name');

            $table->string('alternate_last_name')
                ->nullable()
                ->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'alternate_first_name',
                'alternate_last_name',
            ]);
        });
    }
};
