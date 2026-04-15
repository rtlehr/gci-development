<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketAdminController;

Route::get('/tickets/create', [TicketController::class, 'create'])
    ->name('tickets.create');

Route::post('/tickets', [TicketController::class, 'store'])
    ->name('tickets.store');

Route::get('/admin/tickets', [TicketAdminController::class, 'index'])
    ->name('admin.tickets.index')
    ->middleware('permission:view_admin');

Route::get('/admin/tickets/{ticket}', [TicketAdminController::class, 'show'])
    ->name('admin.tickets.show')
    ->middleware('permission:view_admin');

Route::put('/admin/tickets/{ticket}', [TicketAdminController::class, 'update'])
    ->name('admin.tickets.update')
    ->middleware('permission:view_admin');

Route::post('/admin/tickets/{ticket}/comments', [TicketAdminController::class, 'addComment'])
    ->name('admin.tickets.comments.store')
    ->middleware('permission:view_admin');