<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AlertController;

Route::patch('/alerts/{alert}/read', [AlertController::class, 'markRead'])
    ->name('alerts.read');

Route::patch('/alerts/read-all', [AlertController::class, 'markAllRead'])
    ->name('alerts.readAll');