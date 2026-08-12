<?php

namespace App\Http\Controllers\Portal;

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

        $person->personNotes()->create([
            'entered_by_user_id' => $user?->id,
            'entered_by_name' => $user?->name ?: 'Unknown user',
            'category' => $validated['category'],
            'note' => $validated['note'],
        ]);

        return back()->with('success', 'Person note added successfully.');
    }
}
