<?php

use App\Http\Controllers\Admin\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:view_admin'])
    ->prefix('admin/impersonation')
    ->name('admin.impersonation.')
    ->group(function (): void {
        Route::get('/', [ImpersonationController::class, 'index'])
            ->middleware('permission:view_impersonation_log')
            ->name('index');

        Route::post('/{user}', [ImpersonationController::class, 'start'])
            ->middleware('permission:impersonate_users')
            ->name('start');
    });

Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonation.stop');
