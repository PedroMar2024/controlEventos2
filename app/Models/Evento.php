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
        'fecha_evento'        => 'date',
        // TIME se maneja como string; evita 'datetime:H:i' porque eso espera DATETIME
        'hora_inicio'  => 'string',
        'hora_cierre'  => 'string',
        'capacidad'    => 'integer',
        'precio_evento'=> 'decimal:2',
        'publico'      => 'boolean',
        'reingreso'    => 'boolean',
    ];

    public function adminPersona()
    {
        return $this->belongsTo(\App\Models\Persona::class, 'admin_persona_id');
    }
// NUEVO: relación con tipos de entrada
public function tickets()
{
    return $this->hasMany(\App\Models\EventoTicket::class);
}
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