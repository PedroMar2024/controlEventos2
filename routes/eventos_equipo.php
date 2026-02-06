<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EquipoEventoController;

Route::middleware(['auth'])->group(function () {
    // Equipo del evento (gestión de subadmins) reutilizable por superadmin y admin del evento
    Route::get('/eventos/{evento}/equipo', [EquipoEventoController::class, 'index'])
        ->middleware('can:manageSubadmins,evento')->name('eventos.equipo.index');

    Route::post('/eventos/{evento}/equipo/subadmins', [EquipoEventoController::class, 'store'])
        ->middleware('can:manageSubadmins,evento')->name('eventos.equipo.subadmins.store');

    Route::delete('/eventos/{evento}/equipo/subadmins/{persona}', [EquipoEventoController::class, 'destroy'])
        ->middleware('can:manageSubadmins,evento')->name('eventos.equipo.subadmins.destroy');
});