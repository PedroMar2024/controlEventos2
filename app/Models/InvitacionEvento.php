<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitacionEvento extends Model
{
    protected $table = 'invitaciones_evento';

    protected $fillable = [
        'evento_id',
        'email',
        'token',
        'enviada',
        'fecha_envio',
        'fecha_confirmacion',
        'datos_completados',
    ];

    protected $casts = [
        'enviada' => 'boolean',
        'datos_completados' => 'boolean',
        'fecha_envio' => 'datetime',
        'fecha_confirmacion' => 'datetime',
    ];

    // Relación (opcional) con evento
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}