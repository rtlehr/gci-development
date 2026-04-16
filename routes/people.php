<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PeopleController;

Route::get('/people', [PeopleController::class, 'index'])
    ->name('people.index');

Route::post('/people/preferences', [PeopleController::class, 'savePreferences'])   
    ->name('people.preferences.save');

Route::delete('/people/preferences', [PeopleController::class, 'resetPreferences'])
    ->name('people.preferences.reset');

Route::get('/people/export/csv', [PeopleController::class, 'exportCsv'])
    ->name('people.export.csv');

Route::get('/people/create', [PeopleController::class, 'create'])
    ->name('people.create')
    ->middleware('permission:view_admin');

Route::post('/people', [PeopleController::class, 'store'])
    ->name('people.store')
    ->middleware('permission:view_admin');

Route::get('/people/{id}', [PeopleController::class, 'show'])
    ->name('people.show');

Route::get('/people/{id}/edit', [PeopleController::class, 'edit'])
    ->name('people.edit')
    ->middleware('permission:view_admin');

Route::put('/people/{id}', [PeopleController::class, 'update'])
    ->name('people.update')
    ->middleware('permission:view_admin');

Route::delete('/people/{id}', [PeopleController::class, 'destroy'])
    ->name('people.destroy')
    ->middleware('permission:view_admin');
