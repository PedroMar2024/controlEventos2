<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\EventoInvitadoController;
use App\Http\Controllers\EventoCompraController;
use App\Http\Controllers\AccesoEventoController;
use App\Http\Controllers\TicketSolicitudController;
use App\Http\Controllers\ConfirmacionInvitacionController;
use App\Http\Controllers\QrDemoController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

// ========================================
// RUTAS PÚBLICAS (sin autenticación)
// ========================================

Route::get('/', fn () => view('welcome'));

// Autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Confirmación de invitación (enlace en email)
Route::get('/invitacion/confirmar', [ConfirmacionInvitacionController::class, 'verForm'])
    ->name('invitacion.confirmar');
Route::post('/invitacion/confirmar', [ConfirmacionInvitacionController::class, 'procesarForm'])
    ->name('invitacion.confirmar.procesar');

// Compra pública de entradas (eventos públicos)
Route::get('/eventos/{evento}/comprar', [EventoCompraController::class, 'showForm'])
    ->name('eventos.publico.comprar');
Route::post('/eventos/{evento}/comprar', [EventoCompraController::class, 'procesarCompra'])
    ->name('eventos.publico.comprar.procesar');

// Demo QR (para testing)
Route::get('/demo-qr', [QrDemoController::class, 'show']);

// ========== RUTA PÚBLICA PARA ESCANEAR QR DE INVITACIÓN ==========
// Esta ruta NO requiere autenticación (se accede desde el QR de la invitación)
Route::get('/evento/ingreso', [AccesoEventoController::class, 'procesarQrIngreso'])
    ->name('evento.ingreso');

// ========================================
// RUTAS AUTENTICADAS (requieren login)
// ========================================

Route::middleware(['auth'])->group(function () {
    
    // ---- DASHBOARD ----
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // ---- DEBUG (solo desarrollo) ----
    Route::get('/debug/roles', function () {
        return response()->json([
            'user_id' => auth()->id(),
            'roles'   => auth()->user()?->getRoleNames()->toArray(),
        ]);
    })->name('debug.roles');

    // ========================================
    // GESTIÓN DE EVENTOS
    // Roles: Superadmin, Admin Evento, Subadmin Evento
    // ========================================

    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
    
    // Editar evento (requiere permiso 'update')
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])
        ->middleware('can:update,evento')
        ->name('eventos.edit');
    Route::patch('/eventos/{evento}', [EventoController::class, 'update'])
        ->middleware('can:update,evento')
        ->name('eventos.update');
    
    // Eliminar evento (requiere permiso 'delete')
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])
        ->middleware('can:delete,evento')
        ->name('eventos.destroy');
    
    // Aprobar/Cancelar evento (solo superadmin)
    Route::post('/eventos/{evento}/aprobar', [EventoController::class, 'aprobar'])
        ->middleware('can:approve,evento')
        ->name('eventos.aprobar');
    Route::post('/eventos/{evento}/cancelar', [EventoController::class, 'cancelar'])
        ->middleware('can:cancel,evento')
        ->name('eventos.cancelar');
    
    // Cambiar admin del evento (requiere permiso 'update')
    Route::post('/eventos/{evento}/cambiar-admin', [EventoController::class, 'cambiarAdmin'])
        ->middleware('can:update,evento')
        ->name('eventos.cambiar-admin');

    // ========================================
    // GESTIÓN DE INVITADOS
    // Roles: Superadmin, Admin Evento, Subadmin Evento
    // Policy: manageGuests
    // ========================================

    // Página principal de gestión de invitados
    Route::get('/eventos/{evento}/invitados/gestion', [EventoInvitadoController::class, 'gestion'])
        ->middleware('can:manageGuests,evento')
        ->name('eventos.invitados.gestion');
    
    // Agregar invitado individual
    Route::post('/eventos/{evento}/invitados/agregar', [EventoInvitadoController::class, 'agregarInvitado'])
        ->middleware('can:manageGuests,evento')
        ->name('eventos.invitados.agregar');
    
    // Importar invitados desde Excel
    Route::post('/eventos/{evento}/invitaciones/importar', [EventoInvitadoController::class, 'importarDesdeExcel'])
        ->middleware('can:manageGuests,evento')
        ->name('eventos.invitaciones.importarExcel');
    
    // Eliminar invitado
    Route::delete('/eventos/{evento}/invitados/{invitado}', [EventoInvitadoController::class, 'eliminarInvitado'])
        ->name('eventos.invitados.eliminar');
    
    // Cambiar cantidad de personas permitidas por invitación
    Route::put('/eventos/{evento}/invitados/{invitado}/cambiar-cantidad', [EventoInvitadoController::class, 'cambiarCantidad'])
        ->name('eventos.invitados.cambiar_cantidad');
    
    // ---- ENVÍO DE INVITACIONES ----
    
    // Enviar invitación individual (pedido de confirmación)
    Route::post('/eventos/{evento}/invitaciones/{invitacion}/enviar', [EventoInvitadoController::class, 'enviarInvitacionIndividual'])
        ->middleware('can:manageGuests,evento')
        ->name('eventos.invitaciones.enviar');
    
    // Enviar pedido de confirmación masivo (a todos los pendientes)
    Route::post('/eventos/{evento}/invitaciones/enviar-masivo', [EventoInvitadoController::class, 'enviarInvitacionesMasivo'])
        ->middleware('can:manageGuests,evento')
        ->name('eventos.invitaciones.enviarMasivo');
    
    // Enviar invitación definitiva a confirmados
    Route::post('/eventos/{evento}/invitaciones/enviar-finales', [EventoInvitadoController::class, 'enviarInvitacionesFinales'])
        ->name('eventos.invitaciones.enviarFinales');

    // ========================================
    // CONTROL DE ACCESO AL EVENTO
    // Roles: Superadmin, Admin Evento, Subadmin Evento
    // Policy: manageGuests
    // ========================================

    // Lista de eventos para control de acceso
    Route::get('/accesos', [AccesoEventoController::class, 'index'])
        ->name('accesos.index');
    
    // Página de control de un evento específico
    Route::get('/accesos/{evento}', [AccesoEventoController::class, 'show'])
        ->middleware('can:manageGuests,evento')
        ->name('accesos.show');
    
    // Escanear QR (AJAX)
    Route::post('/accesos/{evento}/escanear-qr', [AccesoEventoController::class, 'escanearQr'])
        ->middleware('can:manageGuests,evento')
        ->name('accesos.escanear-qr');
    
    // Ingreso manual por DNI
    Route::post('/accesos/{evento}/ingreso-manual', [AccesoEventoController::class, 'ingresoManual'])
        ->middleware('can:manageGuests,evento')
        ->name('accesos.ingreso-manual');
    
    // Historial de accesos
    Route::get('/accesos/{evento}/historial', [AccesoEventoController::class, 'historial'])
        ->middleware('can:manageGuests,evento')
        ->name('accesos.historial');

    // ========================================
    // GESTIÓN DE PERSONAS
    // Roles: Todos los autenticados
    // ========================================

    Route::get('/personas/by-email', [PersonaController::class, 'findByEmail'])
        ->name('personas.byEmail');

    // ========================================
    // TICKETS Y SOLICITUDES
    // Roles: Superadmin, Admin Evento, Subadmin Evento
    // ========================================

    // Ver solicitudes de tickets
    Route::get('/admin/tickets/solicitudes', [TicketSolicitudController::class, 'index'])
        ->name('admin.tickets.solicitudes');
    
    // Aprobar solicitud de ticket
    Route::post('/admin/tickets/solicitudes/{id}/aprobar', [TicketSolicitudController::class, 'aprobar'])
        ->name('admin.tickets.solicitudes.aprobar');
});

// ========================================
// MÓDULOS ADICIONALES
// ========================================

// Gestión de equipos de evento (admin + subadmins)
require __DIR__.'/eventos_equipo.php';

// Rutas de autenticación adicionales (registro, recuperar contraseña, etc.)
require __DIR__.'/auth.php';

// Gestión de admins (deshabilitado)
// require __DIR__.'/admins.php';