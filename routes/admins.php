<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;

Route::middleware(['auth'])->group(function () {
    Route::get('/admins', [AdminManagementController::class, 'index'])
        ->middleware('role:superadmin')
        ->name('admins.index');

    // Ver eventos de un admin de eventos
    Route::get('/admins/{user}/events', [AdminManagementController::class, 'events'])
        ->middleware('role:superadmin')
        ->name('admins.events');
});