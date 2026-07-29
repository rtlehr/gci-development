<?php

use App\Http\Controllers\PeopleController;
use Illuminate\Support\Facades\Route;

// Legacy user-facing URLs now point to the Portal equivalents.
Route::get('/people', fn () => redirect()->route('portal.people.index'))
    ->name('people.index');
Route::get('/people/create', fn () => redirect()->route('portal.people.create'))
    ->name('people.create');
Route::get('/people/{id}', fn (int $id) => redirect()->route('portal.people.show', $id))
    ->name('people.show');
Route::get('/people/{id}/edit', fn (int $id) => redirect()->route('portal.people.edit', $id))
    ->name('people.edit');

Route::post('/people/preferences', [PeopleController::class, 'savePreferences'])
    ->name('people.preferences.save')
    ->middleware('permission:access_people');
Route::delete('/people/preferences', [PeopleController::class, 'resetPreferences'])
    ->name('people.preferences.reset')
    ->middleware('permission:access_people');
Route::post('/people/export/csv', [PeopleController::class, 'exportCsv'])
    ->name('people.export.csv')
    ->middleware('permission:access_people');
Route::post('/people', [PeopleController::class, 'store'])
    ->name('people.store')
    ->middleware('permission:create_people');
Route::put('/people/{id}', [PeopleController::class, 'update'])
    ->name('people.update')
    ->middleware('permission:update_people');
Route::delete('/people/{id}', [PeopleController::class, 'destroy'])
    ->name('people.destroy')
    ->middleware('permission:delete_people');
