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

        Route::delete('/admins/{user}', [AdminManagementController::class, 'destroy'])
        ->middleware('role:superadmin')
        ->name('admins.destroy');
    
    Route::get('/admins/{user}/edit', [AdminManagementController::class, 'edit'])
        ->middleware('role:superadmin')
        ->name('admins.edit');
    
    Route::put('/admins/{user}', [AdminManagementController::class, 'update'])
        ->middleware('role:superadmin')
        ->name('admins.update');
});