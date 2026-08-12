<?php

use App\Http\Controllers\Admin\CustomFieldController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/custom-fields')
    ->name('admin.custom-fields.')
    ->middleware(['permission:view_admin', 'permission:access_custom_fields'])
    ->group(function () {
        Route::get('/', [CustomFieldController::class, 'index'])->name('index');
        Route::get('/export', [CustomFieldController::class, 'export'])->middleware('permission:manage_custom_fields')->name('export');
        Route::post('/import', [CustomFieldController::class, 'import'])->middleware('permission:manage_custom_fields')->name('import');
        Route::get('/create', [CustomFieldController::class, 'create'])->middleware('permission:manage_custom_fields')->name('create');
        Route::post('/', [CustomFieldController::class, 'store'])->middleware('permission:manage_custom_fields')->name('store');
        Route::get('/{customField}/edit', [CustomFieldController::class, 'edit'])->middleware('permission:manage_custom_fields')->name('edit');
        Route::put('/{customField}', [CustomFieldController::class, 'update'])->middleware('permission:manage_custom_fields')->name('update');
        Route::delete('/{customField}', [CustomFieldController::class, 'destroy'])->middleware('permission:manage_custom_fields')->name('destroy');
    });
