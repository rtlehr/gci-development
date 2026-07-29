<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SiteSettings/Index', [
            'groups' => SiteSetting::query()
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(fn ($settings, $group) => [
                    'name' => $group,
                    'settings' => $settings->values(),
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
                default => ['sometimes', 'nullable', 'string', 'max:5000'],
            };
        }

        $validated = $request->validate($rules);
        $values = $validated['settings'] ?? [];

        foreach ($settings as $setting) {
            if (array_key_exists((string) $setting->id, $values) || array_key_exists($setting->id, $values)) {
                $setting->update([
                    'value' => $values[$setting->id] ?? $values[(string) $setting->id] ?? null,
                ]);
            }
        }

        $siteSettings->forget();

        return back()->with('success', 'Site settings updated successfully.');
    }
}
