<?php
use App\Http\Controllers\PositionsController;

use Illuminate\Support\Facades\Route;

Route::get('/positions', 
    [PositionsController::class, 'index'])->name('positions.index');

Route::get('/positions/create', 
    [PositionsController::class, 'create'])
        ->name('positions.create')
        ->middleware('permission:view_admin');

Route::get('/positions/{id}/edit', [PositionsController::class, 'edit'])
    ->name('positions.edit');

Route::put('/positions/{id}', [PositionsController::class, 'update'])
    ->name('positions.update');