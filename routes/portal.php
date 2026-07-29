<?php

use App\Http\Controllers\Portal\AlertController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\PositionController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\PositionCandidateController;
use App\Http\Controllers\PositionCustomSkillTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('index');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');

        Route::get('/positions', [PositionController::class, 'index'])
            ->middleware('permission:access_positions')
            ->name('positions.index');

        Route::post('/positions/preferences', [PositionController::class, 'savePreferences'])
            ->middleware('permission:access_positions')
            ->name('positions.preferences.save');

        Route::delete('/positions/preferences', [PositionController::class, 'resetPreferences'])
            ->middleware('permission:access_positions')
            ->name('positions.preferences.reset');

        Route::post('/positions/export/csv', [PositionController::class, 'exportCsv'])
            ->middleware('permission:access_positions')
            ->name('positions.export.csv');

        Route::get('/positions/create', [PositionController::class, 'create'])
            ->middleware('permission:create_positions')
            ->name('positions.create');

        Route::post('/positions', [PositionController::class, 'store'])
            ->middleware('permission:create_positions')
            ->name('positions.store');

        Route::get('/positions/{id}', [PositionController::class, 'show'])
            ->middleware('permission:read_positions')
            ->name('positions.show');

        Route::get('/positions/{id}/edit', [PositionController::class, 'edit'])
            ->middleware('permission:update_positions')
            ->name('positions.edit');

        Route::put('/positions/{id}', [PositionController::class, 'update'])
            ->middleware('permission:update_positions')
            ->name('positions.update');

        Route::delete('/positions/{id}', [PositionController::class, 'destroy'])
            ->middleware('permission:delete_positions')
            ->name('positions.destroy');

        Route::post('/positions/{id}/candidates', [PositionCandidateController::class, 'store'])
            ->middleware('permission:create_candidates')
            ->name('positions.candidates.store');

        Route::post('/positions/{position}/custom-skills', [PositionCustomSkillTaskController::class, 'storeSkill'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-skills.store');

        Route::delete('/positions/{position}/custom-skills/{skill}', [PositionCustomSkillTaskController::class, 'destroySkill'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-skills.destroy');

        Route::post('/positions/{position}/custom-tasks', [PositionCustomSkillTaskController::class, 'storeTask'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-tasks.store');

        Route::delete('/positions/{position}/custom-tasks/{task}', [PositionCustomSkillTaskController::class, 'destroyTask'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-tasks.destroy');

        Route::get('/tickets', [TicketController::class, 'index'])
            ->middleware('permission:portal_view_own_tickets')
            ->name('tickets.index');

        Route::get('/tickets/create', [TicketController::class, 'create'])
            ->middleware('permission:portal_create_tickets')
            ->name('tickets.create');

        Route::post('/tickets', [TicketController::class, 'store'])
            ->middleware('permission:portal_create_tickets')
            ->name('tickets.store');

        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->middleware('permission:portal_view_own_tickets')
            ->name('tickets.show');
    });
