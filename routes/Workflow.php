<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;

Route::get('/workflows', [WorkflowController::class, 'index'])
    ->name('workflows.index')
    ->middleware('permission:view_admin');

Route::get('/workflows/create', [WorkflowController::class, 'create'])
    ->name('workflows.create')
    ->middleware('permission:view_admin');

Route::post('/workflows', [WorkflowController::class, 'store'])
    ->name('workflows.store')
    ->middleware('permission:view_admin');

Route::get('/workflows/{workflow}/edit', [WorkflowController::class, 'edit'])
    ->name('workflows.edit')
    ->middleware('permission:view_admin');

Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])
    ->name('workflows.update')
    ->middleware('permission:view_admin');

Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])
    ->name('workflows.destroy')
    ->middleware('permission:view_admin');

Route::post('/workflows/preferences', [WorkflowController::class, 'savePreferences'])
    ->name('workflows.preferences.save')
    ->middleware('permission:view_admin');

Route::delete('/workflows/preferences', [WorkflowController::class, 'resetPreferences'])
    ->name('workflows.preferences.reset')
    ->middleware('permission:view_admin');

Route::get('/workflows/export/csv', [WorkflowController::class, 'exportCsv'])
    ->name('workflows.export.csv')
    ->middleware('permission:view_admin');