<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPermissionController;

Route::get('/admin/users', [UserPermissionController::class, 'index'])
    ->name('admin.users.index');

Route::get('/admin/users/{user}/permissions', [UserPermissionController::class, 'edit'])
    ->name('admin.users.permissions.edit');

Route::put('/admin/users/{user}/permissions', [UserPermissionController::class, 'update'])
    ->name('admin.users.permissions.update');