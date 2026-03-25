<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketSolicitud;

class TicketSolicitudController extends Controller
{
    // Listado de solicitudes de tickets
    public function index()
    {
        $solicitudes = TicketSolicitud::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.tickets.solicitudes.index', compact('solicitudes'));
    }
    public function aprobar($id)
{
    // 1. Buscar la solicitud por ID
    $solicitud = \App\Models\TicketSolicitud::findOrFail($id);

    // 2. Buscar o crear persona según email (supongamos campo email es obligatorio)
    $persona = \App\Models\Persona::firstOrCreate(
        ['email' => $solicitud->email],
        ['nombre' => $solicitud->nombre]
    );

    // 3. Asociar persona y evento CON ROL invitado en la tabla pivote (si no estaba)
    $evento = \App\Models\Evento::findOrFail($solicitud->evento_id);
    if (!$evento->personas()->where('persona_id', $persona->id)->wherePivot('role', 'invitado')->exists()) {
        $evento->personas()->attach($persona->id, ['role' => 'invitado']);
    }

    // 4. Generar tickets/QR según cantidad (si evento es público)
    if ($evento->publico) {
        for ($i = 0; $i < $solicitud->cantidad; $i++) {
            \App\Models\Ticket::create([
                'evento_id' => $evento->id,
                'persona_id' => $persona->id,
                'ticket_solicitud_id' => $solicitud->id,
                'codigo_qr' => uniqid('QR-'), // Cambiar luego x lógica más segura/única
                'estado' => 'aprobado',
            ]);
        }
    }
    // 5. Marcar la solicitud como aprobada
    $solicitud->estado = 'aprobada';
    $solicitud->save();

    // 6. (Opcional) enviar notificación/mail aquí

    return redirect()->back()->with('success', 'Solicitud aprobada y tickets generados.');
}
}