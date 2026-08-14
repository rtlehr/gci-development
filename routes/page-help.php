<?php

use App\Http\Controllers\PageHelpAdminController;
use App\Http\Controllers\PageHelpController;
use Illuminate\Support\Facades\Route;

Route::get('/page-help/{helpKey}', [PageHelpController::class, 'show'])
    ->where('helpKey', '.*')
    ->middleware('portal-feature:features.help,view_admin')
    ->name('page-help.show');

Route::prefix('admin/page-help')
    ->name('page-help-admin.')
    ->middleware(['permission:view_admin', 'permission:access_page_help'])
    ->group(function (): void {
        Route::get('/', [PageHelpAdminController::class, 'index'])->name('index');

        Route::get('/export', [PageHelpAdminController::class, 'export'])
            ->middleware('permission:manage_page_help')
            ->name('export');

        Route::post('/import', [PageHelpAdminController::class, 'import'])
            ->middleware('permission:manage_page_help')
            ->name('import');

        Route::get('/create', [PageHelpAdminController::class, 'create'])
            ->middleware('permission:manage_page_help')
            ->name('create');

        Route::post('/', [PageHelpAdminController::class, 'store'])
            ->middleware('permission:manage_page_help')
            ->name('store');

        Route::get('/{pageHelp}/edit', [PageHelpAdminController::class, 'edit'])
            ->middleware('permission:manage_page_help')
            ->name('edit');

        Route::put('/{pageHelp}', [PageHelpAdminController::class, 'update'])
            ->middleware('permission:manage_page_help')
            ->name('update');

        Route::delete('/{pageHelp}', [PageHelpAdminController::class, 'destroy'])
            ->middleware('permission:manage_page_help')
            ->name('destroy');
    });
