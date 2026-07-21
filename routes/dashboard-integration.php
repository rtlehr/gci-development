<?php

/*
|--------------------------------------------------------------------------
| Dashboard route integration example
|--------------------------------------------------------------------------
|
| Add ProjectManagerDashboardService to each dashboard route closure and
| use the returned collection for assignedPositions.
|
*/

use App\Services\ProjectManagerDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/dashboard', function (
    Request $request,
    ProjectManagerDashboardService $projectManagerDashboardService
) {
    $user = $request->user();

    $assignedPositions = $user
        ? $projectManagerDashboardService->positionsFor($user)
        : collect();

    return Inertia::render('Dashboard', [
        // Keep your existing alerts and assignedTickets values here.
        'alerts' => [],
        'assignedTickets' => [],
        'assignedPositions' => $assignedPositions,
        'showProjectManagerPositions' => $assignedPositions->isNotEmpty()
            || $user?->roles()->where('roles.name', 'project_manager')->exists(),
    ]);
})->middleware(['auth'])->name('dashboard');
