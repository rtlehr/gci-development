<?php

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => 'features.candidate_opportunities',
                'group' => 'Portal Features',
                'label' => 'Candidate Opportunities',
                'description' => 'Show candidate-facing position opportunities and progress on the Portal dashboard. Candidate administration remains available when disabled.',
                'type' => 'boolean',
                'value' => '1',
                'sort_order' => 40,
            ],
            [
                'key' => 'features.content_pages',
                'group' => 'Portal Features',
                'label' => 'Public Content Pages',
                'description' => 'Show published informational pages in Public and Portal navigation. Content Page administration remains available when disabled.',
                'type' => 'boolean',
                'value' => '1',
                'sort_order' => 50,
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }

        app(SiteSettingsService::class)->forget();
    }

    public function down(): void
    {
        SiteSetting::query()
            ->whereIn('key', [
                'features.candidate_opportunities',
                'features.content_pages',
            ])
            ->delete();

        app(SiteSettingsService::class)->forget();
    }
};
