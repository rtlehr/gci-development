<?php

use App\Http\Controllers\Admin\UserEventLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:view_admin', 'permission:access_user_event_log'])
    ->prefix('admin/user-event-log')
    ->name('admin.user-event-log.')
    ->group(function (): void {
        Route::get('/', [UserEventLogController::class, 'index'])->name('index');
        Route::get('/{date}', [UserEventLogController::class, 'day'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->name('day');
        Route::get('/{date}/users/{user}', [UserEventLogController::class, 'user'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->whereNumber('user')
            ->name('user');
    });

Route::middleware(['permission:view_admin', 'permission:access_user_event_log', 'permission:export_user_event_log'])
    ->prefix('admin/user-event-log')
    ->name('admin.user-event-log.')
    ->group(function (): void {
        Route::get('/export/{format}', [UserEventLogController::class, 'exportRange'])
            ->where('format', 'csv|splunk')
            ->name('export-range');
        Route::get('/{date}/export/{format}', [UserEventLogController::class, 'exportDay'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->where('format', 'csv|splunk')
            ->name('export-day');
        Route::get('/{date}/users/{user}/export/{format}', [UserEventLogController::class, 'exportUser'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->whereNumber('user')
            ->where('format', 'csv|splunk')
            ->name('export-user');
    });
