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
        $invitacion = InvitacionEvento::where('token', $token)->first();

        if (!$invitacion) {
            // Portero no encuentra ficha: puerta cerrada
            return view('invitacion.no_encontrada');
        }
        return view('eventos.invitados.confirmar', [
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

        $invitacion = InvitacionEvento::where('token', $data['token'])->first();

        // Analogia: el portero chequea la ficha en la lista
        if (!$invitacion) {
            return view('eventos.invitados.invitacion_no_encontrada');
        }

        // Si ya recibió la invitación final (masivo con QR), no puede confirmar de nuevo
        if ($invitacion->enviada) {
            return view('eventos.invitados.invitacion_ya_enviada');
        }

        DB::beginTransaction();
        try {
            // Registra SIEMPRE los datos en la invitación (nombre, apellido, dni)
            $invitacion->nombre = $data['nombre'] ?? '';
            $invitacion->apellido = $data['apellido'] ?? '';
            $invitacion->dni = $data['dni'] ?? '';
            $invitacion->datos_completados = true; // Marca como ficha "completa"
            $invitacion->fecha_confirmacion = now();

            if ($data['accion'] === 'confirmar') {
                $invitacion->confirmado = 1;

                // Buscar o actualizar datos de la persona usando updateOrCreate
                $persona = Persona::updateOrCreate(
                    ['email' => $invitacion->email],
                    [
                        'nombre'   => $data['nombre'] ?? '',
                        'apellido' => $data['apellido'] ?? '',
                        'dni'      => $data['dni'] ?? '',
                    ]
                );

                // Asociar la persona al evento como invitado, evitando duplicados
                $evento = Evento::find($invitacion->evento_id);
                if ($evento && method_exists($evento, 'personas')) {
                    $evento->personas()->syncWithoutDetaching([
                        $persona->id => ['role' => 'invitado']
                    ]);
                }
            } else {
                // Si rechaza, solo marca como no confirmado
                $invitacion->confirmado = 0;
                // Los datos igual quedan guardados por arriba
            }

            $invitacion->save();

            DB::commit();

            return view('eventos.invitados.confirmacion_final', ['invitacion' => $invitacion]);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al registrar tu respuesta.');
        }
    }
}