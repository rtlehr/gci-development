<?php
use App\Http\Controllers\PeopleController;

use Illuminate\Support\Facades\Route;

Route::get('/people', 
    [PeopleController::class, 'index'])->name('people.index');

Route::get('/people/create', 
    [PeopleController::class, 'create'])
        ->name('people.create')
        ->middleware('permission:view_admin');
