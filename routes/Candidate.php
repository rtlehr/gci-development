<?php

use App\Http\Controllers\CandidateController;
use Illuminate\Support\Facades\Route;

Route::get('/candidates', fn () => redirect()->route('portal.candidates.index'))
    ->name('candidates.index');
Route::get('/candidates/create', fn () => redirect()->route('portal.candidates.create'))
    ->name('candidates.create');
Route::get('/candidates/{candidate}', fn (int $candidate) => redirect()->route('portal.candidates.show', $candidate))
    ->name('candidates.show');
Route::get('/candidates/{candidate}/edit', fn (int $candidate) => redirect()->route('portal.candidates.edit', $candidate))
    ->name('candidates.edit');

Route::post('/candidates', [CandidateController::class, 'store'])
    ->name('candidates.store')
    ->middleware('permission:create_candidates');
Route::post('/candidates/export/csv', [CandidateController::class, 'exportCsv'])
    ->name('candidates.export.csv')
    ->middleware('permission:access_candidates');
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
