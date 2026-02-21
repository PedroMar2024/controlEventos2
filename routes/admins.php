<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\EventoController; // <--- importar controlador de eventos

Route::middleware(['auth'])->group(function () {
    Route::get('/admins', [AdminManagementController::class, 'index'])
        ->middleware('role:superadmin')
        ->name('admins.index');

    //Route::get('/admins/create', [AdminManagementController::class, 'create'])
        //->middleware('role:superadmin')
        //->name('admins.create');

    //Route::post('/admins', [AdminManagementController::class, 'store'])
      //  ->middleware('role:superadmin')
        //->name('admins.store');

    Route::delete('/admins/{user}', [AdminManagementController::class, 'destroy'])
        ->middleware('role:superadmin')
        ->name('admins.destroy');
    
    Route::get('/admins/{user}/edit', [AdminManagementController::class, 'edit'])
        ->middleware('role:superadmin')
        ->name('admins.edit');
    
    Route::put('/admins/{user}', [AdminManagementController::class, 'update'])
        ->middleware('role:superadmin')
        ->name('admins.update');
        // NUEVA RUTA: Mostrar los eventos de un admin
    Route::get('admins/{admin}/eventos', [AdminManagementController::class, 'eventos'])->name('admins.eventos');

    // ========================== NUEVA RUTA CAMBIO DE ADMIN DE EVENTO ==========================
    // Esta ruta permite cambiar/administar el admin de un evento, solo para superadmin.
    Route::post('/eventos/{evento}/cambiar-admin', [EventoController::class, 'cambiarAdmin'])
        ->middleware('role:superadmin')
        ->name('eventos.cambiar-admin');
    // ==========================================================================================
});