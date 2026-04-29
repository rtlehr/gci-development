<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrganizationController;

Route::resource('organizations', OrganizationController::class)
    ->middleware('permission:view_admin');

Route::post('/organizations/preferences', [OrganizationController::class, 'savePreferences']);

Route::delete('/organizations/preferences', [OrganizationController::class, 'resetPreferences']);

Route::get('/organizations/export/csv', [OrganizationController::class, 'exportCsv']);

