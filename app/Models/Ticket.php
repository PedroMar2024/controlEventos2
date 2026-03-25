<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'evento_id',
        'persona_id',
        'ticket_solicitud_id',
        'codigo_qr',
        'estado',
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function solicitud()
    {
        return $this->belongsTo(TicketSolicitud::class, 'ticket_solicitud_id');
    }
}