<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('key', 'navigation.default_home_page')
            ->update([
                'type' => 'select',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'navigation.default_home_page')
            ->update([
                'type' => 'text',
                'updated_at' => now(),
            ]);
    }
};
