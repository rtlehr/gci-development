<?php

/*
|--------------------------------------------------------------------------
| PMO dashboard integration reference
|--------------------------------------------------------------------------
|
| This is not a complete replacement for routes/web.php.
| Update your existing $renderDashboard closure as shown below.
|
*/

$renderDashboard = function (
    UserResolver $userResolver,
    ProjectManagerDashboardService $projectManagerDashboardService
) {
    $user = $userResolver->resolveUser();

    // Keep your existing assigned ticket query here.
    //$assignedTickets = /* existing query */;

    $assignedPositions = $projectManagerDashboardService
    ->positionsFor($user);

    $hasProjectManagerRole = $user->roles()
        ->where(function ($query) {
            $query->whereRaw('LOWER(name) IN (?, ?)', [
                'project manager',
                'project_manager',
            ])
            ->orWhereRaw('LOWER(label) = ?', ['project manager']);
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
