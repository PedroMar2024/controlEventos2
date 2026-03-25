<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSolicitud extends Model
{
    protected $table = 'ticket_solicitudes';

    // Campos que se pueden cargar de una (asignación masiva)
    protected $fillable = [
        'evento_id',
        'dni',
        'nombre',
        'apellido',
        'email',
        'cantidad',
        'estado',
        'codigos_qr',
    ];

    // El campo QR se guarda como json (varios códigos por compra)
    protected $casts = [
        'codigos_qr' => 'array',
    ];

    // Relación con el evento (esto después nos abre la puerta para traer los nombres de evento, etc.)
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}