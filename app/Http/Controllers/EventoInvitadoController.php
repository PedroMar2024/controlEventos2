<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\InvitacionEvento;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;


class EventoInvitadoController extends Controller
{
    public function index(Evento $evento)
    {
        // Traemos todos los invitados de este evento
        $invitados = InvitacionEvento::where('evento_id', $evento->id)->get();

        return view('eventos.invitados.index', compact('evento', 'invitados'));
    }

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

    public function gestion($eventoId)
    {
        // Buscá el evento por ID
        $evento = Evento::findOrFail($eventoId);

        // Buscá los invitados de ese evento
        $invitados = InvitacionEvento::where('evento_id', $evento->id)->get();

        // Retorná la vista nueva, pasando los datos necesarios
        return view('eventos.invitados.gestion', compact('evento', 'invitados'));
    }
    

public function enviarInvitacionesMasivo($eventoId)
{
    $evento = \App\Models\Evento::findOrFail($eventoId);

    // Trae todas las invitaciones NO enviadas para ese evento
    $pendientes = \App\Models\InvitacionEvento::where('evento_id', $evento->id)
        ->where(function($q) {
            $q->whereNull('enviada')->orWhere('enviada', false);
        })
        ->get();

    $enviadas_ok = [];
    $enviadas_error = [];

    foreach ($pendientes as $inv) {
        try {
            // Si no tiene token, generá uno
            if (empty($inv->token)) {
                $inv->token = Str::random(32);
            }
            // Marcá como enviada y poné fecha
            $inv->enviada = true;
            $inv->fecha_envio = now();
            $inv->save();

            // Armá el link único de confirmación
            $link = url('/invitacion/confirmar?token=' . $inv->token);

            // Mandá el mail (básico, después lo podés personalizar)
            Mail::raw("Te invitaron al evento '{$evento->nombre}'. Confirmá asistencia acá: $link", function ($message) use ($inv) {
                $message->to($inv->email)
                        ->subject('Confirmá tu invitación al evento');
            });

            $enviadas_ok[] = $inv->email;
        } catch (\Throwable $ex) {
            $enviadas_error[] = $inv->email;
        }
    }

    // Respuesta: volvé a la gestión con mensaje
    return redirect()->back()->with('status', 
        "Se enviaron " . count($enviadas_ok) . " invitaciones." . 
        (count($enviadas_error) ? " Fallaron: " . implode(", ", $enviadas_error) : "")
    );
}
}