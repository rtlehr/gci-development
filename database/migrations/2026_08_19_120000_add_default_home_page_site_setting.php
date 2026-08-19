<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'navigation.default_home_page'],
            [
                'group' => 'Navigation',
                'label' => 'Default home page',
                'description' => 'Choose the first screen users see when they open the site. All screens remain available from navigation.',
                'type' => 'select',
                'value' => 'public_home',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'navigation.default_home_page')
            ->delete();
    }
};
