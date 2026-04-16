<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;

Route::get('/admin/permissions', [PermissionController::class, 'index'])
    ->name('admin.permissions.index')
    ->middleware('permission:view_admin');

Route::post('/admin/permissions/preferences', [PermissionController::class, 'savePreferences'])
    ->name('admin.permissions.preferences.save')
    ->middleware('permission:view_admin');

Route::delete('/admin/permissions/preferences', [PermissionController::class, 'resetPreferences'])
    ->name('admin.permissions.preferences.reset')
    ->middleware('permission:view_admin');

Route::get('/admin/permissions/export/csv', [PermissionController::class, 'exportCsv'])
    ->name('admin.permissions.export.csv')
    ->middleware('permission:view_admin');

Route::get('/admin/permissions/create', [PermissionController::class, 'create'])
    ->name('admin.permissions.create')
    ->middleware('permission:view_admin');

Route::post('/admin/permissions', [PermissionController::class, 'store'])
    ->name('admin.permissions.store')
    ->middleware('permission:view_admin');

Route::get('/admin/permissions/{permission}', [PermissionController::class, 'show'])
    ->name('admin.permissions.show')
    ->middleware('permission:view_admin');

Route::get('/admin/permissions/{permission}/edit', [PermissionController::class, 'edit'])
    ->name('admin.permissions.edit')
    ->middleware('permission:view_admin');

Route::put('/admin/permissions/{permission}', [PermissionController::class, 'update'])
    ->name('admin.permissions.update')
    ->middleware('permission:view_admin');

Route::delete('/admin/permissions/{permission}', [PermissionController::class, 'destroy'])
    ->name('admin.permissions.destroy')
    ->middleware('permission:view_admin');