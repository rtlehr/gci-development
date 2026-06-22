<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\PositionCustomSkillTaskController;

Route::get('/job-titles', [JobTitleController::class, 'index'])
    ->name('job-titles.index')
    ->middleware('permission:access_positions');

Route::get('/job-titles/create', [JobTitleController::class, 'create'])
    ->name('job-titles.create')
    ->middleware('permission:update_positions');

Route::post('/job-titles', [JobTitleController::class, 'store'])
    ->name('job-titles.store')
    ->middleware('permission:update_positions');

Route::get('/job-titles/{jobTitle}', [JobTitleController::class, 'show'])
    ->name('job-titles.show')
    ->middleware('permission:access_positions');

Route::get('/job-titles/{jobTitle}/edit', [JobTitleController::class, 'edit'])
    ->name('job-titles.edit')
    ->middleware('permission:update_positions');

Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])
    ->name('job-titles.update')
    ->middleware('permission:update_positions');

Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])
    ->name('job-titles.destroy')
    ->middleware('permission:update_positions');

Route::post('/job-titles/{jobTitle}/skills', [JobTitleController::class, 'storeSkill'])
    ->name('job-titles.skills.store')
    ->middleware('permission:update_positions');

Route::delete('/job-titles/{jobTitle}/skills/{skill}', [JobTitleController::class, 'destroySkill'])
    ->name('job-titles.skills.destroy')
    ->middleware('permission:update_positions');

Route::post('/job-titles/{jobTitle}/tasks', [JobTitleController::class, 'storeTask'])
    ->name('job-titles.tasks.store')
    ->middleware('permission:update_positions');

Route::delete('/job-titles/{jobTitle}/tasks/{task}', [JobTitleController::class, 'destroyTask'])
    ->name('job-titles.tasks.destroy')
    ->middleware('permission:update_positions');

Route::post('/positions/{position}/custom-skills', [PositionCustomSkillTaskController::class, 'storeSkill'])
    ->name('positions.custom-skills.store')
    ->middleware('permission:update_positions');

Route::delete('/positions/{position}/custom-skills/{skill}', [PositionCustomSkillTaskController::class, 'destroySkill'])
    ->name('positions.custom-skills.destroy')
    ->middleware('permission:update_positions');

Route::post('/positions/{position}/custom-tasks', [PositionCustomSkillTaskController::class, 'storeTask'])
    ->name('positions.custom-tasks.store')
    ->middleware('permission:update_positions');

Route::delete('/positions/{position}/custom-tasks/{task}', [PositionCustomSkillTaskController::class, 'destroyTask'])
    ->name('positions.custom-tasks.destroy')
    ->middleware('permission:update_positions');