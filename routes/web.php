<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Alert;
use App\Services\UserResolver;
use Inertia\Inertia;
use App\Models\Person;
use App\Models\Ticket;

use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

Route::get('/', function (UserResolver $userResolver) {

    $user = $userResolver->resolveUser();

    return Inertia::render('Dashboard', [
        'alerts' => Alert::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(10)
            ->get(),

        'assignedTickets' => Ticket::query()
            ->where('assigned_to_user_id', $user->id)
            ->whereNotIn('status', ['complete', 'canceled'])
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'ticket_number',
                'title',
                'request_type',
                'importance',
                'category',
                'status',
                'created_at',
            ]),
    ]);
})->name('home');

Route::get('/dashboard', function (UserResolver $userResolver) {

    $user = $userResolver->resolveUser();

    return Inertia::render('Dashboard', [
        'alerts' => Alert::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(10)
            ->get(),

        'assignedTickets' => Ticket::query()
            ->where('assigned_to_user_id', $user->id)
            ->whereNotIn('status', ['complete', 'canceled'])
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'ticket_number',
                'title',
                'request_type',
                'importance',
                'category',
                'status',
                'created_at',
            ]),
    ]);
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
require __DIR__.'/Organizations.php';
require __DIR__.'/Alerts.php';
require __DIR__.'/Teams.php';
require __DIR__.'/email.php';