<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CandidateController;

Route::get('/candidates', [CandidateController::class, 'index'])
    ->name('candidates.index');

Route::get('/candidates/create', [CandidateController::class, 'create'])
    ->name('candidates.create')
    ->middleware('permission:view_admin');

Route::post('/candidates', [CandidateController::class, 'store'])
    ->name('candidates.store')
    ->middleware('permission:view_admin');

Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
    ->name('candidates.show')
    ->middleware('permission:view_admin');

Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])
    ->name('candidates.edit')
    ->middleware('permission:view_admin');

Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])
    ->name('candidates.update')
    ->middleware('permission:view_admin');

Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])
    ->name('candidates.destroy')
    ->middleware('permission:view_admin');

Route::post('/candidates/preferences', [CandidateController::class, 'savePreferences'])
    ->name('candidates.preferences.save')
    ->middleware('permission:view_admin');

Route::delete('/candidates/preferences', [CandidateController::class, 'resetPreferences'])
    ->name('candidates.preferences.reset')
    ->middleware('permission:view_admin');

Route::get('/candidates/export/csv', [CandidateController::class, 'exportCsv'])
    ->name('candidates.export.csv')
    ->middleware('permission:view_admin');