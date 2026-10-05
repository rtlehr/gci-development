<?php

use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeFormatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:access_portal', 'permission:view_resumes'])->prefix('portal/resumes')->name('portal.resumes.')->group(function () {
    Route::get('/', [ResumeController::class, 'index'])->name('index');
    Route::get('/upload', [ResumeController::class, 'create'])->middleware('permission:manage_resumes')->name('create');
    Route::post('/upload', [ResumeController::class, 'upload'])->middleware('permission:manage_resumes')->name('upload');
    Route::get('/{resume}/edit', [ResumeController::class, 'edit'])->middleware('permission:manage_resumes')->name('edit');
    Route::put('/{resume}', [ResumeController::class, 'update'])->middleware('permission:manage_resumes')->name('update');
    Route::delete('/{resume}', [ResumeController::class, 'destroy'])->middleware('permission:manage_resumes')->name('destroy');
    Route::get('/{resume}/download', [ResumeController::class, 'download'])->name('download');
    Route::post('/{resume}/candidate', [ResumeController::class, 'addCandidate'])->middleware(['permission:create_candidates', 'permission:portal_view_positions'])->name('candidate');
    Route::get('/{resume}', [ResumeController::class, 'show'])->name('show');
});
Route::middleware(['auth', 'permission:view_admin', 'permission:manage_resume_formats'])->prefix('admin/resume-formats')->name('resume-formats.')->group(function () {
    Route::get('/', [ResumeFormatController::class, 'index'])->name('index');
    Route::get('/create', [ResumeFormatController::class, 'create'])->name('create');
    Route::post('/', [ResumeFormatController::class, 'store'])->name('store');
    Route::get('/{format}/edit', [ResumeFormatController::class, 'edit'])->name('edit');
    Route::put('/{format}', [ResumeFormatController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'permission:access_portal', 'permission:update_people', 'permission:view_resumes'])
    ->prefix('portal/people/{person}/resumes')->name('portal.people.resumes.')->group(function () {
        Route::post('/upload', [ResumeController::class, 'uploadForPerson'])->middleware('permission:manage_resumes')->name('upload');
        Route::get('/{resume}/edit', [ResumeController::class, 'editForPerson'])->middleware('permission:manage_resumes')->name('edit');
        Route::put('/{resume}', [ResumeController::class, 'updateForPerson'])->middleware('permission:manage_resumes')->name('update');
        Route::delete('/{resume}', [ResumeController::class, 'destroyForPerson'])->middleware('permission:manage_resumes')->name('destroy');
        Route::get('/{resume}', [ResumeController::class, 'showForPerson'])->name('show');
    });
