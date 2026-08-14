<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSettingsService
{
    private const CACHE_KEY = 'site_settings.values';

    /** @return array<string, mixed> */
    public function all(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return $this->fallbacks();
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = SiteSetting::query()
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get(['key', 'type', 'value']);

            if ($settings->isEmpty()) {
                return $this->fallbacks();
            }

            $values = $this->fallbacks();

            foreach ($settings as $setting) {
                data_set($values, $setting->key, $this->castValue($setting->type, $setting->value));
            }

            return $values;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    public function fallbacks(): array
    {
        return [
            'branding' => [
                'program_mark' => 'ZION',
                'portal_name' => 'INSIGHT Portal',
                'site_name' => 'ZION INSIGHT Portal',
                'primary_color' => '#005c43',
                'primary_hover_color' => '#004734',
                'surface_color' => '#ffffff',
                'page_background_color' => '#f7f8f7',
                'border_color' => '#e3e3e3',
                'text_color' => '#3a3a3a',
            ],
            'program' => [
                'eyebrow' => 'ZION Program',
                'name' => 'ZION INSIGHT Portal',
                'summary' => 'A unified program portal for resources, requests, collaboration, and operational support.',
                'contract_year' => 'Base Year',
                'contract_number' => 'B2026-#########',
                'period_of_performance' => 'May 1, 2026 – April 30, 2027',
            ],
            'home' => [
                'primary_action_label' => 'Open my portal',
                'secondary_action_label' => 'Program details',
                'program_contacts_title' => 'Program contacts',
                'program_contacts_description' => 'Find program leadership, PMO contacts, and customer points of contact.',
                'resources_title' => 'Resources',
                'resources_description' => 'Access approved program guidance, forms, policies, and reference materials.',
                'requests_title' => 'Requests',
                'requests_description' => 'Authenticated users will be able to submit and monitor program requests.',
                'faqs_title' => 'Frequently asked questions',
                'faqs_description' => 'Get clear answers to common questions about the program and portal.',
                'support_title' => 'Support',
                'support_description' => 'Report a problem, request assistance, or follow an existing support ticket.',
                'pmo_title' => 'PMO',
                'pmo_description' => 'Connect with the program management office and operational support team.',
            ],
            'features' => [
                'support_tickets' => true,
                'alerts' => true,
                'help' => true,
                'candidate_opportunities' => true,
                'content_pages' => true,
            ],
            'footer' => [
                'copyright_name' => 'ZION INSIGHT Portal',
                'support_label' => 'Support',
                'faqs_label' => 'FAQs',
            ],
        ];
    }

    private function castValue(string $type, ?string $value): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value ?? 'null', true),
            default => $value,
        };
    }
}
