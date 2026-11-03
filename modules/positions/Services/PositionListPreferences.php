<?php

namespace Insite\Modules\Positions\Services;

use App\Models\UserListPreference;
use App\Services\CustomFieldListService;
use Illuminate\Http\Request;
use Insite\Modules\Positions\Support\ListDefinitions\PositionsDefinition;

/** Owns the column preferences for the module's legacy and Portal Positions lists. */
class PositionListPreferences
{
    public function __construct(private readonly CustomFieldListService $customFields)
    {
    }

    public function save(Request $request, int $userId): void
    {
        $definition = $this->definition();
        $validKeys = collect($definition['columns'])->pluck('key')->all();

        // Preserve the existing request shape and validation behavior.
        $validated = $request->validate([
            'visible_columns' => ['required', 'array'],
            'visible_columns.*' => ['string'],
            'column_order' => ['required', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()->all();
        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()->all();

        UserListPreference::updateOrCreate(
            ['user_id' => $userId, 'list_key' => $definition['list_key']],
            ['visible_columns' => $visibleColumns, 'column_order' => $columnOrder],
        );
    }

    public function reset(int $userId): void
    {
        $definition = $this->definition();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();
    }

    private function definition(): array
    {
        return $this->customFields->augmentDefinition(PositionsDefinition::get(), 'position');
    }
}
