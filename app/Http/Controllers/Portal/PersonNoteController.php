<?php

namespace App\Http\Controllers\Portal;

use App\Services\UserEventLogger;
use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\PersonNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonNoteController extends Controller
{
    public function store(Request $request, Person $person): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(PersonNote::CATEGORIES)],
            'note' => ['required', 'string', 'max:10000'],
        ]);

        $user = $request->user();

        $note = $person->personNotes()->create([
            'entered_by_user_id' => $user?->id,
            'entered_by_name' => $user?->name ?: 'Unknown user',
            'category' => $validated['category'],
            'note' => $validated['note'],
        ]);

        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'people', action: 'add_note', subject: $person,
            subjectLabel: trim(($person->preferred_name ?: $person->first_name).' '.$person->last_name),
            description: 'Added a '.$note->category.' note for '.trim(($person->preferred_name ?: $person->first_name).' '.$person->last_name).'.',
            metadata: ['note_category' => $note->category],
        );

        return back()->with('success', 'Person note added successfully.');
    }
}
