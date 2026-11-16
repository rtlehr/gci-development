<?php

namespace Insite\Modules\Positions\Services;

use App\Services\CustomFieldListService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Insite\Modules\Positions\Support\ListDefinitions\PositionsDefinition;

/** Shared Positions list configuration for module lists, exports, and preferences. */
class PositionListConfiguration
{
    public function __construct(private readonly CustomFieldListService $customFields)
    {
    }

    public function definition(): array
    {
        return $this->customFields->augmentDefinition(PositionsDefinition::get(), 'position');
    }

    /** Keep list and CSV-export status filtering identical. */
    public function applyStatusFilter(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
    }
}
