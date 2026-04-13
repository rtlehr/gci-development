<?php
namespace App\Services;

use App\Models\UserListPreference;

class ListPreferenceService
{
    public static function getUserPreferences($userId, $listKey)
    {
        return UserListPreference::where('user_id', $userId)
            ->where('list_key', $listKey)
            ->first();
    }

    public static function merge($definition, $preferences)
    {
        $columns = collect($definition['columns']);

        // Default visible
        $defaultVisible = $columns
            ->where('default_visible', true)
            ->sortBy('default_order')
            ->pluck('key')
            ->values()
            ->toArray();

        $defaultOrder = $columns
            ->sortBy('default_order')
            ->pluck('key')
            ->values()
            ->toArray();

        if (!$preferences) {
            return [
                'visible' => $defaultVisible,
                'order' => $defaultOrder,
            ];
        }

        $validKeys = $columns->pluck('key')->toArray();

        $visible = collect($preferences->visible_columns ?? [])
            ->filter(fn($col) => in_array($col, $validKeys))
            ->values()
            ->toArray();

        $order = collect($preferences->column_order ?? [])
            ->filter(fn($col) => in_array($col, $validKeys))
            ->values()
            ->toArray();

        // Add missing columns
        foreach ($defaultOrder as $col) {
            if (!in_array($col, $order)) {
                $order[] = $col;
            }
        }

        return [
            'visible' => $visible ?: $defaultVisible,
            'order' => $order,
        ];
    }
}