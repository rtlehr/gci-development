<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PositionCandidateController extends Controller
{
    public function store(Request $request, int|string $id): RedirectResponse
    {
        $position = Position::query()->findOrFail($id);

        $validated = $request->validate([
            'person_id' => [
                'required',
                'integer',
                'exists:people,id',
            ],
            'workflow_id' => [
                'nullable',
                'integer',
                'exists:workflows,id',
            ],
        ]);

        $candidateAlreadyExists = Candidate::query()
            ->where('person_id', $validated['person_id'])
            ->where('position_id', $position->id)
            ->exists();

        if ($candidateAlreadyExists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'person_id' => 'This person is already a candidate for this position.',
                ]);
        }

        $workflow = $this->resolveWorkflow($validated['workflow_id'] ?? null);

        Candidate::query()->create([
            'person_id' => $validated['person_id'],
            'position_id' => $position->id,
            'workflow_id' => $workflow->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $routeName = $request->routeIs('portal.*')
            ? 'portal.positions.edit'
            : 'positions.edit';

        return redirect()
            ->route($routeName, [
                'id' => $position->id,
                'section' => 'candidates',
            ])
            ->with('success', 'Candidate added to the position successfully.');
    }

    private function resolveWorkflow(?int $workflowId): Workflow
    {
        if ($workflowId !== null) {
            $workflow = Workflow::query()
                ->whereKey($workflowId)
                ->where('is_active', true)
                ->first();

            if ($workflow === null) {
                throw ValidationException::withMessages([
                    'workflow_id' => 'The selected workflow is not active.',
                ]);
            }

            return $workflow;
        }

        $workflow = Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->first();

        if ($workflow === null) {
            throw ValidationException::withMessages([
                'workflow_id' => 'No active candidate workflow is configured.',
            ]);
        }

        return $workflow;
    }
}
