<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Person;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::post('/dev/switch-user', function (Request $request) {
    abort_unless(config('app.debug') === true, 403);
    abort_unless(config('devuser.enabled') === true, 403);

    $validated = $request->validate([
        'person_code' => ['required', 'exists:people,person_code'],
    ]);

    session([
        'dev_person_code' => $validated['person_code'],
    ]);

    return redirect('/')
        ->with('success', 'Test user switched.');
})->name('dev.switch-user');

Route::post('/dev/clear-user', function () {
    abort_unless(config('app.debug') === true, 403);
    abort_unless(config('devuser.enabled') === true, 403);

    session()->forget('dev_person_code');

    return redirect('/')
        ->with('success', 'Test user reset.');
})->name('dev.clear-user');

require __DIR__.'/people.php';
require __DIR__.'/position.php';
require __DIR__.'/PositionAssignments.php';
require __DIR__.'/settings.php';
require __DIR__.'/UserPermissions.php';
require __DIR__.'/Permissions.php';
require __DIR__.'/Roles.php';
require __DIR__.'/Ticket.php';
require __DIR__.'/Candidate.php';
require __DIR__.'/Workflow.php';
require __DIR__.'/page-help.php';
require __DIR__.'/Groups.php';