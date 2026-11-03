<?php

namespace Insite\Modules\Positions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\PositionCustomSkill;
use App\Models\PositionCustomTask;
use Illuminate\Http\Request;

/**
 * Module-owned Positions custom requirement actions.
 *
 * The host controller is retained as a transitional fallback for installations
 * where Positions is not selected. Keep both implementations behaviorally aligned
 * until the legacy fallback is retired.
 */
class PositionCustomSkillTaskController extends Controller
{
    public function storeSkill(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requirement_type' => ['required', 'in:required,desired'],
        ]);

        $position->customSkills()->create($validated);

        return back()->with('success', 'Custom skill added.');
    }

    public function destroySkill(Position $position, PositionCustomSkill $skill)
    {
        if ($skill->position_id !== $position->id) {
            abort(404);
        }

        $skill->delete();

        return back()->with('success', 'Custom skill deleted.');
    }

    public function storeTask(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $position->customTasks()->create($validated);

        return back()->with('success', 'Custom task added.');
    }

    public function destroyTask(Position $position, PositionCustomTask $task)
    {
        if ($task->position_id !== $position->id) {
            abort(404);
        }

        $task->delete();

        return back()->with('success', 'Custom task deleted.');
    }
}