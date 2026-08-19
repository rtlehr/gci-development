<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function index(): Response
    {
        $homePageOptions = $this->defaultHomePageOptions();

        return Inertia::render('Admin/SiteSettings/Index', [
            'groups' => SiteSetting::query()
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(fn ($settings, $group) => [
                    'name' => $group,
                    'settings' => $settings->values()->map(function ($setting) use ($homePageOptions) {
                        if ($setting->key === 'navigation.default_home_page') {
                            $setting->setAttribute('options', $homePageOptions);
                        }

                        return $setting;
                    }),
                ])
                ->values(),
        ]);
    }

    public function update(Request $request, SiteSettingsService $siteSettings): RedirectResponse
    {
        $settings = SiteSetting::query()
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        $rules = [];

        foreach ($settings as $setting) {
            $rules["settings.{$setting->id}"] = match ($setting->type) {
                'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'boolean' => ['sometimes', 'boolean'],
                'select' => $setting->key === 'navigation.default_home_page'
                    ? [
                        'sometimes',
                        'required',
                        'string',
                        Rule::in(collect($this->defaultHomePageOptions())->pluck('value')->all()),
                    ]
                    : ['sometimes', 'nullable', 'string', 'max:255'],
                default => ['sometimes', 'nullable', 'string', 'max:5000'],
            };
        }

        $validated = $request->validate($rules);
        $values = $validated['settings'] ?? [];

        foreach ($settings as $setting) {
            if (array_key_exists((string) $setting->id, $values) || array_key_exists($setting->id, $values)) {
                $value = $values[$setting->id] ?? $values[(string) $setting->id] ?? null;

                $setting->update([
                    'value' => $setting->type === 'boolean'
                        ? ($value ? '1' : '0')
                        : $value,
                ]);
            }
        }

        $siteSettings->forget();

        return back()->with('success', 'Site settings updated successfully.');
    }

    /** @return array<int, array{value: string, label: string}> */
    private function defaultHomePageOptions(): array
    {
        $contentPages = ContentPage::query()
            ->published()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('navigation_label')
            ->orderBy('title')
            ->get(['id', 'title', 'navigation_label', 'visibility']);

        return collect([
            ['value' => 'public_home', 'label' => 'Public Home'],
            ['value' => 'my_portal', 'label' => 'My Portal'],
        ])
            ->concat($contentPages->map(fn (ContentPage $page) => [
                'value' => 'content_page:'.$page->id,
                'label' => ($page->navigation_label ?: $page->title).' (Content Page)',
            ]))
            ->values()
            ->all();
    }
}
