<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TeamController;

Route::prefix('admin')->middleware('permission:view_admin')->group(function () {

    // Export CSV
    Route::get('/teams/export/csv', [TeamController::class, 'exportCsv'])
        ->name('teams.export.csv');

    // Column Preferences
    Route::post('/teams/preferences', [TeamController::class, 'savePreferences'])
        ->name('teams.preferences.save');

    Route::delete('/teams/preferences', [TeamController::class, 'resetPreferences'])
        ->name('teams.preferences.reset');

    // CRUD
    Route::resource('teams', TeamController::class);
});