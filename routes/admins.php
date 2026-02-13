<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;

Route::middleware(['auth'])->group(function () {
    Route::get('/admins', [AdminManagementController::class, 'index'])
        ->middleware('role:superadmin')
        ->name('admins.index');

    Route::get('/admins/create', [AdminManagementController::class, 'create'])
        ->middleware('role:superadmin')
        ->name('admins.create');

    Route::post('/admins', [AdminManagementController::class, 'store'])
        ->middleware('role:superadmin')
        ->name('admins.store');

    Route::get('/admins/{user}/events', [AdminManagementController::class, 'events'])
        ->middleware('role:superadmin')
        ->name('admins.events');
});