<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\EventoInvitadoController;
use Maatwebsite\Excel\Facades\Excel; // arriba de tu controlador
use Illuminate\Support\Facades\Log;

Route::get('/', fn () => view('welcome'));

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');

    Route::get('/personas/by-email', [PersonaController::class, 'findByEmail'])->name('personas.byEmail');

    Route::get('/debug/roles', function () {
        return response()->json([
            'user_id' => auth()->id(),
            'roles'   => auth()->user()?->getRoleNames()->toArray(),
        ]);
    })->name('debug.roles');

    // Eventos
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

    // Editar/Actualizar protegidos por Policy
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])
        ->middleware('can:update,evento')->name('eventos.edit');
    Route::patch('/eventos/{evento}', [EventoController::class, 'update'])
        ->middleware('can:update,evento')->name('eventos.update');

    // Eliminar: solo superadmin (Policy)
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])
        ->middleware('can:delete,evento')->name('eventos.destroy');

    // Estado (solo superadmin)
    Route::post('/eventos/{evento}/aprobar', [EventoController::class, 'aprobar'])
        ->middleware('can:approve,evento')->name('eventos.aprobar');
    Route::post('/eventos/{evento}/cancelar', [EventoController::class, 'cancelar'])
        ->middleware('can:cancel,evento')->name('eventos.cancelar');

    // Cambio de admin del evento
    Route::post('/eventos/{evento}/cambiar-admin', [EventoController::class, 'cambiarAdmin'])
        ->middleware('can:update,evento')
        ->name('eventos.cambiar-admin');

    // ========== GESTIÓN DE INVITADOS EN UNA SOLA VISTA ==========
    Route::get('/eventos/{evento}/invitados/gestion', [EventoInvitadoController::class, 'gestion'])
        ->name('eventos.invitados.gestion')
        ->middleware('can:manageGuests,evento');

    Route::post('/eventos/{evento}/invitados/agregar', [EventoInvitadoController::class, 'agregarInvitado'])
        ->name('eventos.invitados.agregar')
        ->middleware('can:manageGuests,evento');

    // Enviar notificaciones pendientes MASIVO
    Route::post('/eventos/{evento}/invitaciones/enviar-masivo', [EventoInvitadoController::class, 'enviarInvitacionesMasivo'])
        ->name('eventos.invitaciones.enviarMasivo')
        ->middleware('can:manageGuests,evento');

    // MASIVO carga pendientes (deja este como estaba)
    Route::post('/eventos/{evento}/invitaciones-pendientes-masivo', [EventoInvitadoController::class, 'cargarInvitacionesMasivo'])
        ->name('eventos.invitaciones.cargarMasivo')
        ->middleware('can:manageGuests,evento');

    // IMPORTAR DESDE EXCEL (AHORA CON NAME UNICO: importarExcel)
    Route::post('/eventos/{evento}/invitaciones/importar', [App\Http\Controllers\EventoInvitadoController::class, 'importarDesdeExcel'])
        ->name('eventos.invitaciones.importarExcel')
        ->middleware('can:manageGuests,evento');

    Route::post('/eventos/{evento}/invitaciones/{invitacion}/enviar', [App\Http\Controllers\EventoInvitadoController::class, 'enviarInvitacionIndividual'])
        ->name('eventos.invitaciones.enviar')
        ->middleware('can:manageGuests,evento');

    Route::get('/invitacion/confirmar', [App\Http\Controllers\ConfirmacionInvitacionController::class, 'verForm'])
        ->name('invitacion.confirmar');
    Route::post('/invitacion/confirmar', [App\Http\Controllers\ConfirmacionInvitacionController::class, 'procesarForm'])
        ->name('invitacion.confirmar.procesar');
    Route::post('/eventos/{evento}/invitaciones/enviar-finales', [App\Http\Controllers\EventoInvitadoController::class, 'enviarInvitacionesFinales'])
        ->name('eventos.invitaciones.enviarFinales');
    Route::delete('/eventos/{evento}/invitados/{invitado}', [App\Http\Controllers\EventoInvitadoController::class, 'eliminarInvitado'])
        ->name('eventos.invitados.eliminar');
});

require __DIR__.'/eventos_equipo.php';
require __DIR__.'/auth.php';
// require __DIR__.'/admins.php'; // <--- Comentada o eliminada