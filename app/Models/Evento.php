<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{

        protected $table = 'eventos';
    
        protected $fillable = [
            'nombre',
            'admin_persona_id',
            'fecha_evento',
            'hora_inicio',
            'hora_cierre',
            'ubicacion',
            'latitud',      // ← NUEVA
            'longitud',     // ← NUEVA
            'localidad',
            'provincia',
            'capacidad',
            'estado',
            'descripcion',
            'precio_evento',
            'publico',
            'reingreso',
        ];
    
        protected $casts = [
            'fecha_evento' => 'date',
            'hora_inicio'  => 'string',
            'hora_cierre'  => 'string',
            'capacidad'    => 'integer',
            'precio_evento'=> 'decimal:2',
            'publico'      => 'boolean',
            'reingreso'    => 'boolean',
            'latitud'      => 'decimal:8',  // ← NUEVA
            'longitud'     => 'decimal:8',  // ← NUEVA
        ];

    // Admin del evento
    public function adminPersona()
    {
        return $this->belongsTo(\App\Models\Persona::class, 'admin_persona_id');
    }

    // TIPOS de entrada configurables para el evento (ej: General, VIP, etc.)
    public function tiposEntrada()
    {
        return $this->hasMany(\App\Models\EventoTicket::class, 'evento_id');
    }

    // TICKETS físicos emitidos (boletos/QRs vinculados a personas)
    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'evento_id');
    }

    // Personas vinculadas al evento (con roles)
    public function personas()
    {
        return $this->belongsToMany(
            \App\Models\Persona::class,
            'event_persona_roles',
            'evento_id',
            'persona_id'
        )->withPivot('role')->withTimestamps();
    }
}