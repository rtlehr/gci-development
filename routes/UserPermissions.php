<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPermissionController;

Route::get('/admin/users', [UserPermissionController::class, 'index'])
    ->name('admin.users.index')
    ->middleware('permission:view_admin');

Route::post('/admin/users/preferences', [UserPermissionController::class, 'savePreferences'])
    ->name('admin.users.preferences.save')
    ->middleware('permission:view_admin');

Route::delete('/admin/users/preferences', [UserPermissionController::class, 'resetPreferences'])
    ->name('admin.users.preferences.reset')
    ->middleware('permission:view_admin');

Route::get('/admin/users/export/csv', [UserPermissionController::class, 'exportCsv'])
    ->name('admin.users.export.csv')
    ->middleware('permission:view_admin');

Route::get('/admin/users/{user}/permissions', [UserPermissionController::class, 'edit'])
    ->name('admin.users.permissions.edit')
    ->middleware('permission:view_admin');

Route::put('/admin/users/{user}/permissions', [UserPermissionController::class, 'update'])
    ->name('admin.users.permissions.update')
    ->middleware('permission:view_admin');