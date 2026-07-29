<?php

use App\Http\Controllers\PositionCandidateController;
use App\Http\Controllers\PositionsController;
use Illuminate\Support\Facades\Route;

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
