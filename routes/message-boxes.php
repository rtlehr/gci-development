<?php

use App\Http\Controllers\Admin\MessageBoxController;
use App\Http\Controllers\MessageBoxInteractionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','permission:view_admin'])->prefix('admin/message-boxes')->name('admin.message-boxes.')->group(function () {
    Route::get('/', [MessageBoxController::class, 'index'])->middleware('permission:manage_message_boxes')->name('index');
    Route::get('/create', [MessageBoxController::class, 'create'])->middleware('permission:manage_message_boxes')->name('create');
    Route::post('/', [MessageBoxController::class, 'store'])->middleware('permission:manage_message_boxes')->name('store');
    Route::get('/{messageBox}/edit', [MessageBoxController::class, 'edit'])->middleware('permission:manage_message_boxes')->name('edit');
    Route::put('/{messageBox}', [MessageBoxController::class, 'update'])->middleware('permission:manage_message_boxes')->name('update');
    Route::delete('/{messageBox}', [MessageBoxController::class, 'destroy'])->middleware('permission:manage_message_boxes')->name('destroy');
});
Route::middleware('auth')->prefix('message-boxes')->name('message-boxes.')->group(function () {
    Route::post('/{messageBox}/seen', [MessageBoxInteractionController::class, 'seen'])->name('seen');
    Route::post('/{messageBox}/dismiss', [MessageBoxInteractionController::class, 'dismiss'])->name('dismiss');
    Route::post('/{messageBox}/submit', [MessageBoxInteractionController::class, 'submit'])->name('submit');
});
