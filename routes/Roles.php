<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

Route::get('/admin/roles', [RoleController::class, 'index'])
    ->name('admin.roles.index')
    ->middleware('permission:view_admin');

Route::get('/admin/roles/create', [RoleController::class, 'create'])
    ->name('admin.roles.create')
    ->middleware('permission:view_admin');

Route::post('/admin/roles', [RoleController::class, 'store'])
    ->name('admin.roles.store')
    ->middleware('permission:view_admin');

Route::get('/admin/roles/{role}/edit', [RoleController::class, 'edit'])
    ->name('admin.roles.edit')
    ->middleware('permission:view_admin');

Route::put('/admin/roles/{role}', [RoleController::class, 'update'])
    ->name('admin.roles.update')
    ->middleware('permission:view_admin');

Route::delete('/admin/roles/{role}', [RoleController::class, 'destroy'])
    ->name('admin.roles.destroy')
    ->middleware('permission:view_admin');