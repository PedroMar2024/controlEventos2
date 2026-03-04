<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\InvitacionEvento;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class EventoInvitadoController extends Controller
{
    // Vista PRINCIPAL de gestión de invitados
    public function gestion(Evento $evento)
    {
        //$evento = Evento::findOrFail($eventoId);
        $invitados = InvitacionEvento::where('evento_id', $evento->id)->get();

        return view('eventos.invitados.gestion', compact('evento', 'invitados'));
    }

    // Form para agregar invitado MANUAL/MASIVO
    public function agregarInvitado(Request $request, Evento $evento) // <--- modelo Evento
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        //$evento = Evento::findOrFail($eventoId);

        if (InvitacionEvento::where('evento_id', $evento->id)->where('email', $request->email)->exists()) {
            return back()->with('status', 'Ese email ya está invitado.');
        }

        InvitacionEvento::create([
            'evento_id' => $evento->id,
            'email' => $request->email,
            'enviada' => false,
            'datos_completados' => false
        ]);
        return back()->with('status', 'Invitado cargado correctamente.');
    }

    // AGREGADO MASIVO (carga desde array, por ejemplo Excel)
    public function cargarInvitacionesMasivo(Request $request, $eventoId)
    {
        $emails = $request->input('emails', []);
        $evento = Evento::findOrFail($eventoId);

        $ok = [];
        $ya_existian = [];

        foreach ($emails as $email) {
            $existe = InvitacionEvento::where('evento_id', $evento->id)
                ->where('email', $email)
                ->exists();

            if ($existe) {
                $ya_existian[] = $email;
                continue;
            }

            InvitacionEvento::create([
                'evento_id' => $evento->id,
                'email' => $email,
                'enviada' => false,
                'datos_completados' => false
            ]);
            $ok[] = $email;
        }

        if (count($ok)) {
            return response()->json([
                'success' => true,
                'mensaje' => 'Se agregaron invitados correctamente (' . count($ok) . ').' .
                    (count($ya_existian) ? ' Algunos ya estaban: ' . implode(', ', $ya_existian) : ''),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se pudo agregar ningún invitado. Todos los emails ya estaban registrados.'
            ]);
        }
    }

    // Envío MASIVO de todas las invitaciones pendientes
    public function enviarInvitacionesMasivo($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);

        $pendientes = InvitacionEvento::where('evento_id', $evento->id)
            ->where(function($q) {
                $q->whereNull('enviada')->orWhere('enviada', false);
            })
            ->get();

        $enviadas_ok = [];
        $enviadas_error = [];

        foreach ($pendientes as $inv) {
            try {
                if (empty($inv->token)) {
                    $inv->token = Str::random(32);
                }
                $inv->enviada = true;
                $inv->fecha_envio = now();
                $inv->save();

                $link = url('/invitacion/confirmar?token=' . $inv->token);

                Mail::raw("Te invitaron al evento '{$evento->nombre}'. Confirmá asistencia acá: $link", function ($message) use ($inv) {
                    $message->to($inv->email)
                            ->subject('Confirmá tu invitación al evento');
                });

                $enviadas_ok[] = $inv->email;
            } catch (\Throwable $ex) {
                $enviadas_error[] = $inv->email;
            }
        }

        return redirect()->back()->with('status', 
            "Se enviaron " . count($enviadas_ok) . " invitaciones." . 
            (count($enviadas_error) ? " Fallaron: " . implode(", ", $enviadas_error) : "")
        );
    }
    public function enviarInvitacionIndividual(Evento $evento, InvitacionEvento $invitacion)
{
    // Chequeo: ¿el invitado pertenece a este evento?
    if ($invitacion->evento_id !== $evento->id) {
        return back()->with('status', 'La invitación no corresponde a este evento.');
    }

    // Si ya está enviada, no hacemos nada
    if ($invitacion->enviada) {
        return back()->with('status', 'La invitación ya fue enviada.');
    }

    try {
        // Generar token si no tiene
        if (empty($invitacion->token)) {
            $invitacion->token = \Illuminate\Support\Str::random(32);
        }
        // Marcar como enviada y fecha
        $invitacion->enviada = true;
        $invitacion->fecha_envio = now();
        $invitacion->save();

        // Armá el link único de confirmación
        $link = url('/invitacion/confirmar?token=' . $invitacion->token);

        // Enviar el mail
        \Mail::raw(
            "Te invitaron al evento '{$evento->nombre}'. Confirmá asistencia acá: $link",
            function ($message) use ($invitacion) {
                $message->to($invitacion->email)
                        ->subject('Confirmá tu invitación al evento');
            }
        );

        return back()->with('status', 'Invitación enviada correctamente.');
    } catch (\Throwable $ex) {
        return back()->with('status', 'Ocurrió un error al enviar la invitación.');
    }
}
public function importarDesdeExcel(Request $request, Evento $evento)
{
    // 1. Validamos el archivo subido: debe ser xls o xlsx.
    $request->validate([
        'archivo_invitados' => 'required|file|mimes:xls,xlsx'
    ]);

    try {
        // 2. Cargamos todas las filas de la primera hoja del Excel.
        $file = $request->file('archivo_invitados');
        $rows = \Excel::toArray([], $file)[0];

        $emails_nuevos = 0;
        $emails_existentes = 0;

        // 3. Recorremos cada fila y procesamos email.
        foreach ($rows as $index => $fila) {
            // Si es la primera fila y tiene el encabezado con "mail", la salteamos.
            if ($index === 0 && isset($fila[0]) && str_contains(strtolower($fila[0]), 'mail')) {
                continue;
            }

            $email = trim($fila[0] ?? '');

            // Si el email tiene formato válido...
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // ...chequeamos si ya existe para este evento.
                $ya_existe = \App\Models\InvitacionEvento::where('evento_id', $evento->id)
                    ->where('email', $email)
                    ->exists();

                // Si no existe, lo guardamos como pendiente.
                if (!$ya_existe) {
                    \App\Models\InvitacionEvento::create([
                        'evento_id' => $evento->id,
                        'email' => $email,
                        'enviada' => false,
                        'datos_completados' => false
                    ]);
                    $emails_nuevos++;
                } else {
                    $emails_existentes++;
                }
            }
        }
        // 4. Volvemos con mensaje de resultado al usuario.
        return back()->with('status', "Carga finalizada: $emails_nuevos nuevos, $emails_existentes ya existían.");
    } catch (\Throwable $ex) {
        \Log::error('Error en importación XLS: ' . $ex->getMessage());
        return back()->with('status', 'Ocurrió un error al procesar el archivo.');
    }
}
public function enviarInvitacionesFinales($eventoId)
{
    $evento = \App\Models\Evento::findOrFail($eventoId);

    // Buscar invitados confirmados y que NO hayan recibido la invitación final
    $invitados = \App\Models\InvitacionEvento::where('evento_id', $evento->id)
        ->where('confirmado', 1)
        ->where(function($q) {
            $q->whereNull('enviada')->orWhere('enviada', false);
        })
        ->get();

    $enviadas_ok = [];
    $enviadas_error = [];

    foreach ($invitados as $inv) {
        try {
            // Podés generar un contenido más completo o poner el QR cuando esté
            \Mail::raw(
                "¡Hola! Recibiste tu invitación final al evento '{$evento->nombre}'.",
                function ($message) use ($inv) {
                    $message->to($inv->email)
                            ->subject('Invitación Final al Evento');
                }
            );
            // Marcá como enviada
            $inv->enviada = true;
            $inv->fecha_envio = now();
            $inv->save();
            $enviadas_ok[] = $inv->email;
        } catch (\Throwable $ex) {
            $enviadas_error[] = $inv->email;
        }
    }

    // Volvé con mensaje al usuario
    return redirect()->back()->with('status',
        'Invitaciones finales enviadas: ' . count($enviadas_ok) .
        ($enviadas_error ? '. Errores: ' . implode(', ', $enviadas_error) : '')
    );
}
public function eliminarInvitado($eventoId, $invitadoId)
{
    $evento = \App\Models\Evento::findOrFail($eventoId);
    $invitacion = \App\Models\InvitacionEvento::findOrFail($invitadoId);

    // Busca la persona por email
    $persona = \App\Models\Persona::where('email', $invitacion->email)->first();
    $userEliminado = false;

    // Borra la relación del evento y la invitación
    if ($persona) {
        $evento->personas()->detach($persona->id);
    }
    $invitacion->delete();

    // Chequea si la persona queda huérfana de eventos
    if ($persona && $persona->eventos()->count() === 0) {
        // Borra el usuario si existe
        if ($persona->user) {
            $persona->user->delete();
            $userEliminado = true;
        }
        $persona->delete();
    }

    $msg = "Invitado eliminado correctamente.";
    if ($userEliminado) {
        $msg .= " Se eliminó el usuario relacionado, porque ya no tenía otros eventos.";
    }

    return redirect()->back()->with('status', $msg);
}
}