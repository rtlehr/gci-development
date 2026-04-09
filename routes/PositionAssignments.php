<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PositionAssignmentsController;

Route::get('/position-assignments/create', [PositionAssignmentsController::class, 'create'])
    ->name('position-assignments.create');

Route::post('/position-assignments', [PositionAssignmentsController::class, 'store'])
    ->name('position-assignments.store');

Route::get('/position-assignments/{id}/edit', [PositionAssignmentsController::class, 'edit'])
    ->name('position-assignments.edit');

Route::put('/position-assignments/{id}', [PositionAssignmentsController::class, 'update'])
    ->name('position-assignments.update');

Route::delete('/position-assignments/{id}', [PositionAssignmentsController::class, 'destroy'])
    ->name('position-assignments.destroy');
    


