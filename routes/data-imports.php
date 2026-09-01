<?php

use App\Http\Controllers\Admin\DataImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:view_admin', 'permission:access_data_import'])
    ->prefix('admin/data-imports')->name('admin.data-imports.')
    ->group(function (): void {
        Route::get('/', [DataImportController::class, 'index'])->name('index');
        Route::get('/template/download', [DataImportController::class, 'downloadTemplate'])->name('template.download')->middleware('permission:manage_data_import');
        Route::get('/create', [DataImportController::class, 'create'])->name('create')->middleware('permission:manage_data_import');
        Route::post('/', [DataImportController::class, 'store'])->name('store')->middleware('permission:manage_data_import');
        Route::get('/{dataImport}', [DataImportController::class, 'show'])->name('show');
        Route::put('/{dataImport}/worksheet', [DataImportController::class, 'selectWorksheet'])->name('worksheet')->middleware('permission:manage_data_import');
        Route::put('/{dataImport}/mapping', [DataImportController::class, 'saveMapping'])->name('mapping')->middleware('permission:manage_data_import');
        Route::post('/{dataImport}/mapping-template', [DataImportController::class, 'saveTemplate'])->name('mapping-template')->middleware('permission:manage_data_import');
        Route::post('/{dataImport}/validate', [DataImportController::class, 'validateImport'])->name('validate')->middleware('permission:manage_data_import');
        Route::post('/{dataImport}/execute', [DataImportController::class, 'executeImport'])->name('execute')->middleware('permission:manage_data_import');
        Route::post('/{dataImport}/rollback', [DataImportController::class, 'rollbackImport'])->name('rollback')->middleware('permission:rollback_data_import');
        Route::put('/{dataImport}/rows/{dataImportRow}/resolution', [DataImportController::class, 'resolveRow'])->name('rows.resolution')->middleware('permission:manage_data_import');
        Route::post('/{dataImport}/value-translations', [DataImportController::class, 'saveValueTranslation'])->name('value-translations')->middleware('permission:manage_data_import');
    });
