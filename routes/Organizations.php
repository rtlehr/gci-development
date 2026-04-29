<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;

Route::middleware('permission:view_admin')->group(function () {
    Route::post('/admin/organizations/preferences', [OrganizationController::class, 'savePreferences'])
        ->name('admin.organizations.preferences.save');

    Route::delete('/admin/organizations/preferences', [OrganizationController::class, 'resetPreferences'])
        ->name('admin.organizations.preferences.reset');

    Route::get('/admin/organizations/export/csv', [OrganizationController::class, 'exportCsv'])
        ->name('admin.organizations.export.csv');

    Route::resource('admin/organizations', OrganizationController::class)
        ->names('admin.organizations');
});