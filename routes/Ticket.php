<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketAdminController;

Route::get('/tickets/create', fn () => redirect()->route('portal.tickets.create'))
    ->middleware('portal-feature:features.support_tickets')
    ->name('tickets.create');

Route::post('/tickets', [TicketController::class, 'store'])
    ->name('tickets.store')
    ->middleware(['permission:create_tickets', 'portal-feature:features.support_tickets']);

Route::get('/admin/tickets', [TicketAdminController::class, 'index'])   
    ->name('admin.tickets.index')
    ->middleware('permission:access_tickets');

Route::post('/admin/tickets/preferences', [TicketAdminController::class, 'savePreferences'])
    ->name('admin.tickets.preferences.save')
    ->middleware('permission:access_tickets');

Route::delete('/admin/tickets/preferences', [TicketAdminController::class, 'resetPreferences'])
    ->name('admin.tickets.preferences.reset')
    ->middleware('permission:access_tickets');

Route::get('/admin/tickets/export/csv', [TicketAdminController::class, 'exportCsv'])
    ->name('admin.tickets.export.csv')
    ->middleware('permission:access_tickets');

Route::get('/admin/tickets/{ticket}', [TicketAdminController::class, 'show'])
    ->name('admin.tickets.show')
    ->middleware('permission:read_tickets');

Route::put('/admin/tickets/{ticket}', [TicketAdminController::class, 'update'])
    ->name('admin.tickets.update')
    ->middleware('permission:update_tickets');

Route::patch('/admin/tickets/{ticket}/assign', [TicketController::class, 'assign'])
    ->name('admin.tickets.assign')
    ->middleware('permission:update_tickets');

Route::post('/admin/tickets/{ticket}/comments', [TicketAdminController::class, 'addComment'])
    ->name('admin.tickets.comments.store')
    ->middleware('permission:update_tickets');

Route::post('/admin/tickets/{ticket}/watch', [TicketAdminController::class, 'watch'])
    ->name('admin.tickets.watch')
    ->middleware('permission:read_tickets');

Route::delete('/admin/tickets/{ticket}/watch', [TicketAdminController::class, 'unwatch'])
    ->name('admin.tickets.unwatch')
    ->middleware('permission:read_tickets');