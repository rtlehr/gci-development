<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

require __DIR__.'/people.php';
require __DIR__.'/position.php';
require __DIR__.'/PositionAssignments.php';
require __DIR__.'/settings.php';