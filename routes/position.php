<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PositionsController;

Route::get('/positions', [PositionsController::class, 'index'])
    ->name('positions.index');

Route::get('/positions/create', [PositionsController::class, 'create'])
    ->name('positions.create')
    ->middleware('permission:view_admin');

Route::post('/positions', [PositionsController::class, 'store'])
    ->name('positions.store')
    ->middleware('permission:view_admin');

Route::get('/positions/{id}', [PositionsController::class, 'show'])
    ->name('positions.show');

Route::get('/positions/{id}/edit', [PositionsController::class, 'edit'])
    ->name('positions.edit')
    ->middleware('permission:view_admin');

Route::put('/positions/{id}', [PositionsController::class, 'update'])
    ->name('positions.update')
    ->middleware('permission:view_admin');

Route::delete('/positions/{id}', [PositionsController::class, 'destroy'])
    ->name('positions.destroy')
    ->middleware('permission:view_admin');
