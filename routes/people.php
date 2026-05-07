<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PeopleController;

Route::get('/people', [PeopleController::class, 'index'])
    ->name('people.index')
    ->middleware('permission:access_people');

Route::post('/people/preferences', [PeopleController::class, 'savePreferences'])   
    ->name('people.preferences.save')
    ->middleware('permission:access_people');

Route::delete('/people/preferences', [PeopleController::class, 'resetPreferences'])
    ->name('people.preferences.reset')
    ->middleware('permission:access_people');

Route::get('/people/export/csv', [PeopleController::class, 'exportCsv'])
    ->name('people.export.csv')
    ->middleware('permission:access_people');

Route::get('/people/create', [PeopleController::class, 'create'])
    ->name('people.create')
    ->middleware('permission:create_people');

Route::post('/people', [PeopleController::class, 'store'])
    ->name('people.store')
    ->middleware('permission:create_people');

Route::get('/people/{id}', [PeopleController::class, 'show'])
    ->name('people.show')
    ->middleware('permission:read_people');

Route::get('/people/{id}/edit', [PeopleController::class, 'edit'])
    ->name('people.edit')
    ->middleware('permission:update_people');

Route::put('/people/{id}', [PeopleController::class, 'update'])
    ->name('people.update')
    ->middleware('permission:update_people');

Route::delete('/people/{id}', [PeopleController::class, 'destroy'])
    ->name('people.destroy')
    ->middleware('permission:delete_people');
