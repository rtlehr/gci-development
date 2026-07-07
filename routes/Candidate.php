<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CandidateController;

Route::get('/candidates', [CandidateController::class, 'index'])
    ->name('candidates.index')
    ->middleware('permission:access_candidates');

Route::get('/candidates/create', [CandidateController::class, 'create'])
    ->name('candidates.create')
    ->middleware('permission:create_candidates');

Route::post('/candidates', [CandidateController::class, 'store'])
    ->name('candidates.store')
    ->middleware('permission:create_candidates');

Route::post('/candidates/export/csv', [CandidateController::class, 'exportCsv'])
    ->name('candidates.export.csv')
    ->middleware('permission:access_candidates');

Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
    ->name('candidates.show')
    ->middleware('permission:read_candidates');

Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])
    ->name('candidates.edit')
    ->middleware('permission:update_candidates');

Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])
    ->name('candidates.update')
    ->middleware('permission:update_candidates');

Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])
    ->name('candidates.destroy')
    ->middleware('permission:delete_candidates');

Route::post('/candidates/preferences', [CandidateController::class, 'savePreferences'])
    ->name('candidates.preferences.save')
    ->middleware('permission:access_candidates');

Route::delete('/candidates/preferences', [CandidateController::class, 'resetPreferences'])
    ->name('candidates.preferences.reset')
    ->middleware('permission:access_candidates');

