<?php

use App\Http\Controllers\SiteSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/site-settings')
    ->name('admin.site-settings.')
    ->middleware('permission:view_admin')
    ->group(function () {
        Route::get('/', [SiteSettingController::class, 'index'])->name('index');
        Route::put('/', [SiteSettingController::class, 'update'])->name('update');
    });
