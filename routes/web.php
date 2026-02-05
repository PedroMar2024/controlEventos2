<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\PersonaController; // <- faltaba

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

    // Cambiamos {id} -> {evento} para usar Route Model Binding con Policies
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
    Route::patch('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');

    // Acciones de estado protegidas por Policy (superadmin via before)
    Route::post('/eventos/{evento}/aprobar', [EventoController::class, 'aprobar'])
        ->middleware('can:approve,evento')->name('eventos.aprobar');
    Route::post('/eventos/{evento}/cancelar', [EventoController::class, 'cancelar'])
        ->middleware('can:cancel,evento')->name('eventos.cancelar');
});

require __DIR__.'/auth.php';