<?php

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

require __DIR__.'/public.php';
require __DIR__.'/portal.php';

Route::get('/admin', fn () => Inertia::render('Admin/Index'))
    ->name('admin.index')
    ->middleware('permission:view_admin');

Route::get('/admin/component-showcase', fn () => Inertia::render('Admin/ComponentShowcase'))
    ->name('admin.component-showcase')
    ->middleware(['permission:view_admin', 'permission:access_component_showcase']);

Route::get('/dashboard', fn () => redirect()->route('portal.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::post('/dev/switch-user', function (Request $request) {
    abort_unless(app()->environment('local'), 404);
    abort_unless(config('devuser.enabled') === true, 404);

    $validated = $request->validate([
        'person_code' => ['required', 'exists:people,person_code'],
    ]);

    $person = Person::query()
        ->where('person_code', $validated['person_code'])
        ->whereNotNull('user_id')
        ->firstOrFail();

    session([
        'dev_person_code' => $person->person_code,
    ]);

    Auth::loginUsingId($person->user_id);
    $request->session()->regenerate();

    return redirect('/')
        ->with('success', 'Development user switched.');
})->name('dev.switch-user');

Route::post('/dev/clear-user', function (Request $request) {
    abort_unless(app()->environment('local'), 404);
    abort_unless(config('devuser.enabled') === true, 404);

    session()->forget('dev_person_code');

    $defaultPersonCode = config('devuser.person_code');

    $person = Person::query()
        ->where('person_code', $defaultPersonCode)
        ->whereNotNull('user_id')
        ->first();

    if ($person) {
        Auth::loginUsingId($person->user_id);
        $request->session()->regenerate();
    } else {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect('/')
        ->with('success', 'Development user reset.');
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
require __DIR__.'/jobTitles.php';
require __DIR__.'/site-settings.php';
require __DIR__.'/custom-fields.php';

require __DIR__.'/content-pages.php';

require __DIR__.'/impersonation.php';
require __DIR__.'/user-event-log.php';
