<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GroupController;

Route::prefix('admin')->middleware('permission:view_admin')->group(function () {
    Route::get('/groups/export/csv', [GroupController::class, 'exportCsv'])
        ->name('groups.export.csv');

    Route::post('/groups/preferences', [GroupController::class, 'savePreferences'])
        ->name('groups.preferences.save');

    Route::delete('/groups/preferences', [GroupController::class, 'resetPreferences'])
        ->name('groups.preferences.reset');

    Route::resource('groups', GroupController::class);
});