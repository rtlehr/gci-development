<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PositionsController;

Route::get('/positions', [PositionsController::class, 'index'])
    ->name('positions.index')
    ->middleware('permission:access_positions');

Route::post('/positions/preferences', [PositionsController::class, 'savePreferences'])
    ->name('positions.preferences.save')
    ->middleware('permission:access_positions');

Route::post('/positions/export/csv', [PositionsController::class, 'exportCsv'])
    ->name('positions.export.csv')
    ->middleware('permission:access_positions');

Route::get('/positions/create', [PositionsController::class, 'create'])
    ->name('positions.create')
    ->middleware('permission:create_positions');

Route::post('/positions', [PositionsController::class, 'store'])
    ->name('positions.store')
    ->middleware('permission:create_positions');

Route::get('/positions/{id}', [PositionsController::class, 'show'])
    ->name('positions.show');

Route::get('/positions/{id}/edit', [PositionsController::class, 'edit'])
    ->name('positions.edit')
    ->middleware('permission:update_positions');

Route::put('/positions/{id}', [PositionsController::class, 'update'])
    ->name('positions.update')
    ->middleware('permission:update_positions');

Route::delete('/positions/{id}', [PositionsController::class, 'destroy'])
    ->name('positions.destroy')
    ->middleware('permission:delete_positions');