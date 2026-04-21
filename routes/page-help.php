<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PageHelpController;

use App\Http\Controllers\PageHelpAdminController;

Route::get('/page-help/{helpKey}', [PageHelpController::class, 'show'])
    ->where('helpKey', '.*')
    ->name('page-help.show');

Route::prefix('admin/page-help')
    ->name('page-help-admin.')
    ->middleware('permission:view_admin')
    ->group(function () {
        Route::get('/', [PageHelpAdminController::class, 'index'])->name('index');
        Route::get('/create', [PageHelpAdminController::class, 'create'])->name('create');
        Route::post('/', [PageHelpAdminController::class, 'store'])->name('store');
        Route::get('/{pageHelp}/edit', [PageHelpAdminController::class, 'edit'])->name('edit');
        Route::put('/{pageHelp}', [PageHelpAdminController::class, 'update'])->name('update');
        Route::delete('/{pageHelp}', [PageHelpAdminController::class, 'destroy'])->name('destroy');
    });