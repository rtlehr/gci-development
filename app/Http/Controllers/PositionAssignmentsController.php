<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Http\Request;

class PositionAssignmentsController extends Controller
{
    public function create(Request $request)
    {
        $people = Person::orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'person_code']);

        $positions = Position::orderBy('job_title')
            ->get(['id', 'job_title', 'position_code']);

        return inertia('PositionAssignments/Create', [
            'people' => $people,
            'positions' => $positions,
            'prefill' => [
                'person_id' => $request->input('person_id'),
                'position_id' => $request->input('position_id'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'person_id' => ['required', 'exists:people,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignment_status' => ['required', 'in:active,ended,planned'],
            'assignment_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment = PositionAssignment::create($validated);

        return redirect()
            ->route('people.show', $assignment->person_id)
            ->with('success', 'Assignment created successfully.');
    }

    public function edit(Request $request, $id)
    {
        $assignment = PositionAssignment::findOrFail($id);

        $people = Person::orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'person_code']);

        $positions = Position::orderBy('job_title')
            ->get(['id', 'job_title', 'position_code']);

        return inertia('PositionAssignments/Edit', [
            'assignment' => $assignment,
            'people' => $people,
            'positions' => $positions,
            'return_to' => $request->input('return_to'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = PositionAssignment::findOrFail($id);

        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'person_id' => ['required', 'exists:people,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignment_status' => ['required', 'in:active,ended,planned'],
            'assignment_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'return_to' => ['nullable', 'string'],
        ]);

        $returnTo = $validated['return_to'] ?? null;
        unset($validated['return_to']);

        $assignment->update($validated);

        if ($returnTo) {
            return redirect($returnTo)->with('success', 'Assignment updated successfully.');
        }

        return redirect()
            ->route('people.show', $assignment->person_id)
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $assignment = PositionAssignment::findOrFail($id);

        $returnTo = $request->input('return_to');

        $personId = $assignment->person_id;
        $assignment->delete();

        if ($returnTo) {
            return redirect($returnTo)->with('success', 'Assignment deleted successfully.');
        }

        return redirect()
            ->route('people.show', $personId)
            ->with('success', 'Assignment deleted successfully.');
    }

}