<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('/reports', function () {
    return Inertia::render('Reports/Index');
})->middleware('security:3');

Route::get('/admin', function () {
    return Inertia::render('Admin/Index');
})->middleware('security:3');

require __DIR__.'/settings.php';
