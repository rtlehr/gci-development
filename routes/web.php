<?php

use App\Models\Alert;
use App\Models\Person;
use App\Models\Ticket;
use App\Services\ProjectManagerDashboardService;
use App\Services\UserResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/admin', fn () => Inertia::render('Admin/Index'))
    ->name('admin.index')
    ->middleware('permission:view_admin');

Route::get('/admin/component-showcase', fn () => Inertia::render('Admin/ComponentShowcase'))
    ->name('admin.component-showcase')
    ->middleware('permission:view_admin');

$renderDashboard = function (
    UserResolver $userResolver,
    ProjectManagerDashboardService $projectManagerDashboardService
) {
    $user = $userResolver->resolveUser();

    $assignedTickets = Ticket::query()
        ->where(function ($query) use ($user) {
            $query->where('assigned_to_user_id', $user->id)
                ->orWhereHas('assignedUsers', function ($assignedUserQuery) use ($user) {
                    $assignedUserQuery->where('users.id', $user->id);
                });
        })
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
        ]);

    $assignedPositions = $projectManagerDashboardService
        ->positionsFor($user);

    $hasProjectManagerRole = $user->roles()
        ->where(function ($query) {
            $query->whereRaw('LOWER(name) IN (?, ?)', [
                'project manager',
                'project_manager',
            ])->orWhereRaw('LOWER(label) = ?', [
                'project manager',
            ]);
        })
        ->exists();

    $hasPmoRole = $user->roles()
        ->whereRaw('LOWER(name) = ?', ['pmo'])
        ->exists();

    $pmoPositions = $hasPmoRole
        ? $projectManagerDashboardService->allPositionsForPmo()
        : collect();

    return Inertia::render('Dashboard', [
        'alerts' => Alert::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(10)
            ->get(),

        'assignedTickets' => $assignedTickets,

        'assignedPositions' => $assignedPositions,
        'showProjectManagerPositions' => $hasProjectManagerRole
            || $assignedPositions->isNotEmpty(),

        'pmoPositions' => $pmoPositions,
        'showPmoPositions' => $hasPmoRole,
    ]);
};

Route::get('/', $renderDashboard)->name('home');
Route::get('/dashboard', $renderDashboard)->name('dashboard');

Route::post('/dev/switch-user', function (Request $request) {
    abort_unless(config('app.debug') === true, 403);
    abort_unless(config('devuser.enabled') === true, 403);

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
        ->with('success', 'Test user switched.');
})->name('dev.switch-user');

Route::post('/dev/clear-user', function (Request $request) {
    abort_unless(config('app.debug') === true, 403);
    abort_unless(config('devuser.enabled') === true, 403);

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
require __DIR__.'/jobTitles.php';