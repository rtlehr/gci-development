<?php

// Phase 5C.4: the selected Positions module owns the legacy /positions URLs.
// Keep the exact existing route names, controller actions, permissions and URLs.
// Phase 5C.5 also registers the modern /portal/positions routes below.
// Do not expose legacy actions when the transitional business integration is off.
if (! (bool) config('insite_modules.legacy_positions_enabled', true)) {
    return;
}


use App\Http\Controllers\PositionCandidateController;
use App\Http\Controllers\PositionsController;
use App\Http\Controllers\Portal\PositionController;
use Insite\Modules\Positions\Http\Controllers\PositionCustomSkillTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['insite-module:positions', 'web'])->group(function (): void {
    Route::get('/positions', fn () => redirect()->route('portal.positions.index'))
        ->name('positions.index');
    Route::get('/positions/create', fn () => redirect()->route('portal.positions.create'))
        ->name('positions.create');
    Route::get('/positions/{id}', fn (int $id) => redirect()->route('portal.positions.show', $id))
        ->name('positions.show');
    Route::get('/positions/{id}/edit', fn (int $id) => redirect()->route('portal.positions.edit', $id))
        ->name('positions.edit');

    Route::post('/positions/preferences', [PositionsController::class, 'savePreferences'])
        ->name('positions.preferences.save')
        ->middleware('permission:access_positions');
    Route::post('/positions/export/csv', [PositionsController::class, 'exportCsv'])
        ->name('positions.export.csv')
        ->middleware('permission:access_positions');
    Route::post('/positions', [PositionsController::class, 'store'])
        ->name('positions.store')
        ->middleware('permission:create_positions');
    Route::put('/positions/{id}', [PositionsController::class, 'update'])
        ->name('positions.update')
        ->middleware('permission:update_positions');
    Route::delete('/positions/{id}', [PositionsController::class, 'destroy'])
        ->name('positions.destroy')
        ->middleware('permission:delete_positions');
    Route::post('/positions/{id}/candidates', [PositionCandidateController::class, 'store'])
        ->name('positions.candidates.store')
        ->middleware('permission:create_candidates');
});

// Phase 5C.5: modern portal Positions endpoints are owned by the selected module.
// Route names, verbs, parameters, actions, and permissions are identical to the
// transitional host definitions in routes/portal.php. The module guard also
// protects cached routes if deployment selection subsequently changes.
// Include the web group for sessions and implicit Eloquent route model binding.
// Keep the module guard ahead of auth so disabled modules return 404.
Route::middleware(['insite-module:positions', 'web', 'auth', 'permission:access_portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/positions', [PositionController::class, 'index'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.index');

        Route::post('/positions/preferences', [PositionController::class, 'savePreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.preferences.save');

        Route::delete('/positions/preferences', [PositionController::class, 'resetPreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.preferences.reset');

        Route::post('/positions/export/csv', [PositionController::class, 'exportCsv'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.export.csv');

        Route::get('/positions/create', [PositionController::class, 'create'])
            ->middleware('permission:create_positions')
            ->name('positions.create');

        Route::post('/positions', [PositionController::class, 'store'])
            ->middleware('permission:create_positions')
            ->name('positions.store');

        Route::get('/positions/{id}', [PositionController::class, 'show'])
            ->middleware('permission:portal_view_positions')
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
    });
