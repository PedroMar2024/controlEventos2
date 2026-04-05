<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccesoEvento extends Model
{
    protected $table = 'accesos_evento';

    protected $fillable = [
        'evento_id',
        'invitacion_id',
        'tipo',
        'fecha_hora',
        'metodo',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function invitacion()
    {
        return $this->belongsTo(InvitacionEvento::class);
    }

    public function persona()
    {
        return $this->hasOneThrough(
            Persona::class,
            InvitacionEvento::class,
            'id',
            'email',
            'invitacion_id',
            'email'
        );
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function esEntrada(): bool
    {
        return $this->tipo === 'entrada';
    }

    public function esSalida(): bool
    {
        return $this->tipo === 'salida';
    }
}