<?php

use App\Http\Controllers\Portal\AlertController;
use Illuminate\Support\Facades\Route;

Route::patch('/alerts/read-all', [AlertController::class, 'markAllRead'])
    ->name('alerts.readAll');

Route::patch('/alerts/{alert}/read', [AlertController::class, 'markRead'])
    ->name('alerts.read');