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

    public function procesarForm(Request $request)
{
    \Log::info('==[INICIO procesarForm]==', $request->all());

    try {
        // 1. Buscar invitación por token
        $token = $request->input('token');
        $invitacion = \App\Models\InvitacionEvento::where('token', $token)->first();
        if (!$invitacion) {
            \Log::warning('==[NO SE ENCONTRÓ INVITACION]==', ['token' => $token]);
            return view('eventos.invitados.invitacion_no_encontrada');
        }

        // 2. Armar reglas de validación dinámicas
        $rules = [
            'token'    => 'required|string',
            'accion'   => 'required|in:confirmar,rechazar',
        ];
        if (!$invitacion->datos_completados) {
            $rules['nombre']   = 'required|string|max:255';
            $rules['apellido'] = 'required|string|max:255';
            $rules['dni']      = 'required|string|max:32';
        }

        $data = $request->validate($rules);
        \Log::info('==[VALIDEZ DATA]==', $data);

        \DB::beginTransaction();
        try {
            // 3. Actualizar SOLO los campos que existen en invitaciones_evento
            $invitacion->datos_completados = true;
            $invitacion->fecha_confirmacion = now();
            $invitacion->confirmado = ($data['accion'] === 'confirmar' ? 1 : 0);
            $invitacion->save();
            \Log::info('==[GUARDADA invitacion]==', $invitacion->fresh()->toArray());

            // 4. Si llegaron los datos personales, actualizar/crear persona
            if (isset($data['nombre']) && isset($data['apellido']) && isset($data['dni'])) {
                \Log::info('==[ANTES DE UPDATEORCREATE persona]', [
                    'email' => $invitacion->email,
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'dni' => $data['dni'],
                ]);
                $persona = \App\Models\Persona::updateOrCreate(
                    ['email' => $invitacion->email],
                    [
                        'nombre'   => $data['nombre'],
                        'apellido' => $data['apellido'],
                        'dni'      => $data['dni'],
                    ]
                );
                \Log::info('==[GUARDADA persona]', $persona->toArray());

                // VINCULAR PERSONA-EVENTO si hay relación
                $evento = \App\Models\Evento::find($invitacion->evento_id);
                \Log::info('==[BUSCADO evento]', ['id' => $invitacion->evento_id, 'encontrado' => $evento ? true : false]);
                if ($evento && method_exists($evento, 'personas')) {
                    $evento->personas()->syncWithoutDetaching([
                        $persona->id => ['role' => 'invitado']
                    ]);
                    \Log::info('==[ASOCIADA persona con evento]', ['persona_id' => $persona->id, 'evento_id' => $evento->id]);
                }
            }

            \DB::commit();
            \Log::info('==[FIN OK procesarForm]==', ['token' => $data['token']]);
            return view('eventos.invitados.confirmacion_final', ['invitacion' => $invitacion]);
        } catch (\Throwable $ex2) {
            \DB::rollBack();
            \Log::error('==[ERROR DENTRO DE LA TRANSACCION]==', [
                'mensaje' => $ex2->getMessage(),
                'trace' => $ex2->getTraceAsString()
            ]);
            throw $ex2;
        }
    } catch (\Throwable $ex) {
        \Log::error('==[ERROR GLOBAL EN procesarForm]==', [
            'mensaje' => $ex->getMessage(),
            'trace' => $ex->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Ocurrió un error al registrar tu respuesta.');
    }
}
}