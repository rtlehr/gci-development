<?php

use App\Http\Controllers\Portal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('index');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
    });
