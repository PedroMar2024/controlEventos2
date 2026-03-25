<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\EventoInvitadoController;
use App\Http\Controllers\EventoCompraController;
use Maatwebsite\Excel\Facades\Excel;
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

    // ---- Eventos ----
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])
        ->middleware('can:update,evento')->name('eventos.edit');
    Route::patch('/eventos/{evento}', [EventoController::class, 'update'])
        ->middleware('can:update,evento')->name('eventos.update');
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])
        ->middleware('can:delete,evento')->name('eventos.destroy');
    Route::post('/eventos/{evento}/aprobar', [EventoController::class, 'aprobar'])
        ->middleware('can:approve,evento')->name('eventos.aprobar');
    Route::post('/eventos/{evento}/cancelar', [EventoController::class, 'cancelar'])
        ->middleware('can:cancel,evento')->name('eventos.cancelar');
    Route::post('/eventos/{evento}/cambiar-admin', [EventoController::class, 'cambiarAdmin'])
        ->middleware('can:update,evento')
        ->name('eventos.cambiar-admin');

    // ---- INVITADOS Y NOTIFICACIONES ----
    // Gestión y listado principal:
    Route::get('/eventos/{evento}/invitados/gestion', [EventoInvitadoController::class, 'gestion'])
        ->name('eventos.invitados.gestion')
        ->middleware('can:manageGuests,evento');
    
    // Agregar individual:
    Route::post('/eventos/{evento}/invitados/agregar', [EventoInvitadoController::class, 'agregarInvitado'])
        ->name('eventos.invitados.agregar')
        ->middleware('can:manageGuests,evento');
    
    // Importar desde Excel (único flujo masivo):
    Route::post('/eventos/{evento}/invitaciones/importar', [EventoInvitadoController::class, 'importarDesdeExcel'])
        ->name('eventos.invitaciones.importarExcel')
        ->middleware('can:manageGuests,evento');
    
    // Eliminar invitado:
    Route::delete('/eventos/{evento}/invitados/{invitado}', [EventoInvitadoController::class, 'eliminarInvitado'])
        ->name('eventos.invitados.eliminar');
    
    // Enviar notificaciones individuales:
    Route::post('/eventos/{evento}/invitaciones/{invitacion}/enviar', [EventoInvitadoController::class, 'enviarInvitacionIndividual'])
        ->name('eventos.invitaciones.enviar')
        ->middleware('can:manageGuests,evento');

    // Enviar notificaciones pendientes MASIVO:
    Route::post('/eventos/{evento}/invitaciones/enviar-masivo', [EventoInvitadoController::class, 'enviarInvitacionesMasivo'])
        ->name('eventos.invitaciones.enviarMasivo')
        ->middleware('can:manageGuests,evento');
    
    // Enviar a confirmados:
    Route::post('/eventos/{evento}/invitaciones/enviar-finales', [EventoInvitadoController::class, 'enviarInvitacionesFinales'])
        ->name('eventos.invitaciones.enviarFinales');

    // ---- CONFIRMACIONES ----
    Route::get('/invitacion/confirmar', [App\Http\Controllers\ConfirmacionInvitacionController::class, 'verForm'])
        ->name('invitacion.confirmar');
    Route::post('/invitacion/confirmar', [App\Http\Controllers\ConfirmacionInvitacionController::class, 'procesarForm'])
        ->name('invitacion.confirmar.procesar');
        Route::get('/admin/tickets/solicitudes', [TicketSolicitudController::class, 'index'])
        ->name('admin.tickets.solicitudes');
});
Route::get('/eventos/{evento}/comprar', [EventoCompraController::class, 'showForm'])
    ->name('eventos.publico.comprar');

Route::post('/eventos/{evento}/comprar', [EventoCompraController::class, 'procesarCompra'])
    ->name('eventos.publico.comprar.procesar');
    // Procesar solicitud de compra de entradas para eventos públicos (POST)

// ---- Otros módulos ----
require __DIR__.'/eventos_equipo.php';
require __DIR__.'/auth.php';
// require __DIR__.'/admins.php'; // Este módulo está deshabilitado
