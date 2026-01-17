<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoTicket extends Model
{
    protected $table = 'evento_tickets';

    protected $fillable = [
        'evento_id',
        'nombre',
        'precio',
        'cupo',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cupo'   => 'integer',
        'activo' => 'boolean',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }
}