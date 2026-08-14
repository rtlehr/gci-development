<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        $settings = [
            [
                'key' => 'features.support_tickets',
                'group' => 'Portal Features',
                'label' => 'Support Tickets',
                'description' => 'Allow portal users to submit and review support tickets. Administrative ticket management remains available when disabled.',
                'type' => 'boolean',
                'value' => '1',
                'sort_order' => 10,
            ],
            [
                'key' => 'features.alerts',
                'group' => 'Portal Features',
                'label' => 'Alerts',
                'description' => 'Show alerts, notification counts, and alert history in the Public and Portal experience. Administrative alert access remains available when disabled.',
                'type' => 'boolean',
                'value' => '1',
                'sort_order' => 20,
            ],
            [
                'key' => 'features.help',
                'group' => 'Portal Features',
                'label' => 'Help',
                'description' => 'Show contextual Help buttons and Help content in the Public and Portal experience. Page Help administration remains available when disabled.',
                'type' => 'boolean',
                'value' => '1',
                'sort_order' => 30,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [...$setting, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')
            ->whereIn('key', [
                'features.support_tickets',
                'features.alerts',
                'features.help',
            ])
            ->delete();
    }
};
