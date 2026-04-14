<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPermissionController;

Route::get('/admin/users', [UserPermissionController::class, 'index'])
    ->name('admin.users.index')
    ->middleware('permission:view_admin');

Route::get('/admin/users/{user}/permissions', [UserPermissionController::class, 'edit'])
    ->name('admin.users.permissions.edit')
    ->middleware('permission:view_admin');

Route::put('/admin/users/{user}/permissions', [UserPermissionController::class, 'update'])
    ->name('admin.users.permissions.update')
    ->middleware('permission:view_admin');