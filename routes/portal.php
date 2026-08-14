<?php

use App\Http\Controllers\Portal\AlertController;
use App\Http\Controllers\Portal\CandidateController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\PositionController;
use App\Http\Controllers\Portal\PeopleController;
use App\Http\Controllers\Portal\PersonNoteController;
use App\Http\Controllers\Portal\JobTitleController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\PositionCandidateController;
use App\Http\Controllers\PositionCustomSkillTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:access_portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)
            ->middleware('permission:portal_view_dashboard')
            ->name('index');
        Route::get('/dashboard', DashboardController::class)
            ->middleware('permission:portal_view_dashboard')
            ->name('dashboard');
        Route::post('/dashboard/staffing/preferences', [DashboardController::class, 'saveStaffingPreferences'])
            ->middleware('permission:portal_view_dashboard')
            ->name('dashboard.staffing.preferences.save');
        Route::delete('/dashboard/staffing/preferences', [DashboardController::class, 'resetStaffingPreferences'])
            ->middleware('permission:portal_view_dashboard')
            ->name('dashboard.staffing.preferences.reset');
        Route::post('/dashboard/staffing/export/csv', [DashboardController::class, 'exportStaffingCsv'])
            ->middleware('permission:portal_view_dashboard')
            ->name('dashboard.staffing.export.csv');
        Route::get('/alerts', [AlertController::class, 'index'])
            ->middleware('portal-feature:features.alerts,view_admin')
            ->name('alerts.index');


        Route::get('/people', [PeopleController::class, 'index'])
            ->middleware('permission:portal_view_directory')
            ->name('people.index');

        Route::post('/people/preferences', [PeopleController::class, 'savePreferences'])
            ->middleware('permission:portal_view_directory')
            ->name('people.preferences.save');

        Route::delete('/people/preferences', [PeopleController::class, 'resetPreferences'])
            ->middleware('permission:portal_view_directory')
            ->name('people.preferences.reset');

        Route::post('/people/export/csv', [PeopleController::class, 'exportCsv'])
            ->middleware('permission:portal_view_directory')
            ->name('people.export.csv');

        Route::get('/people/create', [PeopleController::class, 'create'])
            ->middleware('permission:create_people')
            ->name('people.create');

        Route::post('/people', [PeopleController::class, 'store'])
            ->middleware('permission:create_people')
            ->name('people.store');

        Route::get('/people/{id}', [PeopleController::class, 'show'])
            ->middleware('permission:portal_view_directory')
            ->name('people.show');

        Route::get('/people/{id}/edit', [PeopleController::class, 'edit'])
            ->middleware('permission:update_people')
            ->name('people.edit');

        Route::put('/people/{id}', [PeopleController::class, 'update'])
            ->middleware('permission:update_people')
            ->name('people.update');

        Route::post('/people/{person}/notes', [PersonNoteController::class, 'store'])
            ->middleware('permission:update_people')
            ->name('people.notes.store');

        Route::delete('/people/{id}', [PeopleController::class, 'destroy'])
            ->middleware('permission:delete_people')
            ->name('people.destroy');


        Route::get('/job-title-requirements', [JobTitleController::class, 'requirementsIndex'])
            ->middleware('permission:portal_view_positions')
            ->name('job-title-requirements.index');

        Route::get('/job-titles', [JobTitleController::class, 'index'])
            ->middleware('permission:portal_view_positions')
            ->name('job-titles.index');
        Route::post('/job-titles/preferences', [JobTitleController::class, 'savePreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('job-titles.preferences.save');
        Route::delete('/job-titles/preferences', [JobTitleController::class, 'resetPreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('job-titles.preferences.reset');
        Route::get('/job-titles/create', [JobTitleController::class, 'create'])
            ->middleware('permission:update_positions')
            ->name('job-titles.create');
        Route::post('/job-titles', [JobTitleController::class, 'store'])
            ->middleware('permission:update_positions')
            ->name('job-titles.store');
        Route::get('/job-titles/{jobTitle}', [JobTitleController::class, 'show'])
            ->middleware('permission:portal_view_positions')
            ->name('job-titles.show');
        Route::get('/job-titles/{jobTitle}/edit', [JobTitleController::class, 'edit'])
            ->middleware('permission:update_positions')
            ->name('job-titles.edit');
        Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])
            ->middleware('permission:update_positions')
            ->name('job-titles.update');
        Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])
            ->middleware('permission:update_positions')
            ->name('job-titles.destroy');
        Route::post('/job-titles/{jobTitle}/skills', [JobTitleController::class, 'storeSkill'])
            ->middleware('permission:update_positions')
            ->name('job-titles.skills.store');
        Route::put('/job-titles/{jobTitle}/skills/{skill}', [JobTitleController::class, 'updateSkill'])
            ->middleware('permission:update_positions')
            ->name('job-titles.skills.update');
        Route::delete('/job-titles/{jobTitle}/skills/{skill}', [JobTitleController::class, 'destroySkill'])
            ->middleware('permission:update_positions')
            ->name('job-titles.skills.destroy');
        Route::post('/job-titles/{jobTitle}/tasks', [JobTitleController::class, 'storeTask'])
            ->middleware('permission:update_positions')
            ->name('job-titles.tasks.store');
        Route::put('/job-titles/{jobTitle}/tasks/{task}', [JobTitleController::class, 'updateTask'])
            ->middleware('permission:update_positions')
            ->name('job-titles.tasks.update');
        Route::delete('/job-titles/{jobTitle}/tasks/{task}', [JobTitleController::class, 'destroyTask'])
            ->middleware('permission:update_positions')
            ->name('job-titles.tasks.destroy');

        Route::get('/positions', [PositionController::class, 'index'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.index');

        Route::post('/positions/preferences', [PositionController::class, 'savePreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.preferences.save');

        Route::delete('/positions/preferences', [PositionController::class, 'resetPreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.preferences.reset');

        Route::post('/positions/export/csv', [PositionController::class, 'exportCsv'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.export.csv');

        Route::get('/positions/create', [PositionController::class, 'create'])
            ->middleware('permission:create_positions')
            ->name('positions.create');

        Route::post('/positions', [PositionController::class, 'store'])
            ->middleware('permission:create_positions')
            ->name('positions.store');

        Route::get('/positions/{id}', [PositionController::class, 'show'])
            ->middleware('permission:portal_view_positions')
            ->name('positions.show');

        Route::get('/positions/{id}/edit', [PositionController::class, 'edit'])
            ->middleware('permission:update_positions')
            ->name('positions.edit');

        Route::put('/positions/{id}', [PositionController::class, 'update'])
            ->middleware('permission:update_positions')
            ->name('positions.update');

        Route::delete('/positions/{id}', [PositionController::class, 'destroy'])
            ->middleware('permission:delete_positions')
            ->name('positions.destroy');

        Route::post('/positions/{id}/candidates', [PositionCandidateController::class, 'store'])
            ->middleware('permission:create_candidates')
            ->name('positions.candidates.store');

        Route::post('/positions/{position}/custom-skills', [PositionCustomSkillTaskController::class, 'storeSkill'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-skills.store');

        Route::delete('/positions/{position}/custom-skills/{skill}', [PositionCustomSkillTaskController::class, 'destroySkill'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-skills.destroy');

        Route::post('/positions/{position}/custom-tasks', [PositionCustomSkillTaskController::class, 'storeTask'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-tasks.store');

        Route::delete('/positions/{position}/custom-tasks/{task}', [PositionCustomSkillTaskController::class, 'destroyTask'])
            ->middleware('permission:update_positions')
            ->name('positions.custom-tasks.destroy');

        Route::get('/candidates', [CandidateController::class, 'index'])
            ->middleware('permission:portal_view_positions')
            ->name('candidates.index');

        Route::post('/candidates/preferences', [CandidateController::class, 'savePreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('candidates.preferences.save');

        Route::delete('/candidates/preferences', [CandidateController::class, 'resetPreferences'])
            ->middleware('permission:portal_view_positions')
            ->name('candidates.preferences.reset');

        Route::post('/candidates/export/csv', [CandidateController::class, 'exportCsv'])
            ->middleware('permission:portal_view_positions')
            ->name('candidates.export.csv');

        Route::get('/candidates/create', [CandidateController::class, 'create'])
            ->middleware('permission:create_candidates')
            ->name('candidates.create');

        Route::post('/candidates', [CandidateController::class, 'store'])
            ->middleware('permission:create_candidates')
            ->name('candidates.store');

        Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
            ->middleware('permission:portal_view_positions')
            ->name('candidates.show');

        Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])
            ->middleware('permission:update_candidates')
            ->name('candidates.edit');

        Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])
            ->middleware('permission:update_candidates')
            ->name('candidates.update');

        Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])
            ->middleware('permission:delete_candidates')
            ->name('candidates.destroy');

        Route::get('/tickets', [TicketController::class, 'index'])
            ->middleware(['permission:portal_view_own_tickets', 'portal-feature:features.support_tickets'])
            ->name('tickets.index');

        Route::get('/tickets/create', [TicketController::class, 'create'])
            ->middleware(['permission:portal_create_tickets', 'portal-feature:features.support_tickets'])
            ->name('tickets.create');

        Route::post('/tickets', [TicketController::class, 'store'])
            ->middleware(['permission:portal_create_tickets', 'portal-feature:features.support_tickets'])
            ->name('tickets.store');

        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->middleware(['permission:portal_view_own_tickets', 'portal-feature:features.support_tickets'])
            ->name('tickets.show');
    });
