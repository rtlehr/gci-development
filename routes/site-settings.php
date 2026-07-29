<?php

use App\Http\Controllers\SiteSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/site-settings')
    ->name('admin.site-settings.')
    ->middleware(['permission:view_admin', 'permission:access_site_settings'])
    ->group(function (): void {
        Route::get('/', [SiteSettingController::class, 'index'])->name('index');

        Route::put('/', [SiteSettingController::class, 'update'])
            ->middleware('permission:update_site_settings')
            ->name('update');
    });
