<?php

use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Public\ContentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/pages/{contentPage:slug}', [ContentPageController::class, 'show'])
    ->name('content-pages.show');

Route::prefix('admin/content-pages')
    ->name('admin.content-pages.')
    ->middleware('permission:view_admin')
    ->group(function () {
        Route::get('/', [AdminContentPageController::class, 'index'])->name('index');
        Route::get('/create', [AdminContentPageController::class, 'create'])->name('create');
        Route::post('/', [AdminContentPageController::class, 'store'])->name('store');
        Route::get('/{contentPage}/edit', [AdminContentPageController::class, 'edit'])->name('edit');
        Route::put('/{contentPage}', [AdminContentPageController::class, 'update'])->name('update');
        Route::delete('/{contentPage}', [AdminContentPageController::class, 'destroy'])->name('destroy');
    });
