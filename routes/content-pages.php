<?php

use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Public\ContentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/pages/{contentPage:slug}', [ContentPageController::class, 'show'])
    ->name('content-pages.show');

Route::prefix('admin/content-pages')
    ->name('admin.content-pages.')
    ->middleware(['permission:view_admin', 'permission:access_content_pages'])
    ->group(function () {
        Route::get('/', [AdminContentPageController::class, 'index'])->name('index');
        Route::get('/create', [AdminContentPageController::class, 'create'])->middleware('permission:manage_content_pages')->name('create');
        Route::post('/', [AdminContentPageController::class, 'store'])->middleware('permission:manage_content_pages')->name('store');
        Route::get('/{contentPage}/edit', [AdminContentPageController::class, 'edit'])->middleware('permission:manage_content_pages')->name('edit');
        Route::put('/{contentPage}', [AdminContentPageController::class, 'update'])->middleware('permission:manage_content_pages')->name('update');
        Route::delete('/{contentPage}', [AdminContentPageController::class, 'destroy'])->middleware('permission:manage_content_pages')->name('destroy');
    });
