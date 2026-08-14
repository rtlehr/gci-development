<?php

use App\Http\Controllers\Portal\AlertController;
use Illuminate\Support\Facades\Route;

Route::patch('/alerts/read-all', [AlertController::class, 'markAllRead'])
    ->middleware('portal-feature:features.alerts,view_admin')
    ->name('alerts.readAll');

Route::patch('/alerts/{alert}/read', [AlertController::class, 'markRead'])
    ->middleware('portal-feature:features.alerts,view_admin')
    ->name('alerts.read');