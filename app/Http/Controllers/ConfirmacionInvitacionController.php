<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvitacionEvento;
use App\Models\Persona;
use App\Models\Evento;
use Illuminate\Support\Facades\DB;

class ConfirmacionInvitacionController extends Controller
{
    // Muestra el formulario para confirmar/rechazar una invitación
    public function verForm(Request $request)
    {
        $token = $request->query('token');
        $invitacion = \App\Models\InvitacionEvento::where('token', $token)->first();
    
        if (!$invitacion) {
            return view('invitacion.no_encontrada');
        }
        return view('invitacion.confirmar', [
            'invitacion' => $invitacion,
        ]);
    }

    // Procesa la respuesta de confirmación/rechazo
    public function procesarForm(Request $request)
{
    $data = $request->validate([
        'token'      => 'required|string',
        'accion'     => 'required|in:confirmar,rechazar',
        'nombre'     => 'nullable|string|max:255',
        'apellido'   => 'nullable|string|max:255',
        'dni'        => 'nullable|string|max:32',
    ]);

    $invitacion = \App\Models\InvitacionEvento::where('token', $data['token'])->first();

    // Analogia: es como si el portero chequea la invitación en la lista.
    if (!$invitacion) {
        // Si la invitación fue borrada, no deja entrar.
        return view('eventos.invitados.invitacion_no_encontrada');
    }

    // Si ya recibió la invitación final (por ejemplo con QR), no puede confirmar de nuevo.
    if ($invitacion->enviada) {
        return view('eventos.invitados.invitacion_ya_enviada');
    }

    \DB::beginTransaction();
    try {
        if ($data['accion'] === 'confirmar') {
            $invitacion->confirmado = 1;
            $invitacion->fecha_confirmacion = now();
            $invitacion->datos_completados = true;

            // Buscar o crear persona (por email)
            $persona = \App\Models\Persona::where('email', $invitacion->email)->first();
            if (!$persona) {
                $persona = \App\Models\Persona::create([
                    'nombre'   => $data['nombre'] ?? '',
                    'apellido' => $data['apellido'] ?? '',
                    'dni'      => $data['dni'] ?? '',
                    'email'    => $invitacion->email,
                ]);
            }

            // Asociar la persona al evento como invitado (evita duplicados)
            $evento = \App\Models\Evento::find($invitacion->evento_id);
            if ($evento && method_exists($evento, 'personas')) {
                $evento->personas()->syncWithoutDetaching([$persona->id => ['role' => 'invitado']]);
            }
        } else {
            // Si rechaza
            $invitacion->confirmado = 0;
            $invitacion->fecha_confirmacion = now();
            $invitacion->datos_completados = true;
        }
        $invitacion->save();

        \DB::commit();

        // Opcional: mostrar una vista final/simple
        return view('eventos.invitados.confirmacion_final', ['invitacion' => $invitacion]);
    } catch (\Throwable $ex) {
        \DB::rollBack();
        return redirect()->back()->with('error', 'Ocurrió un error al registrar tu respuesta.');
    }
}
}