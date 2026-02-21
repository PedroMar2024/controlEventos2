<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $fillable = [
        'nombre', 'apellido', 'dni', 'telefono', 'email', 'direccion',
    ];

    // Un persona puede tener múltiples usuarios (si lo necesitas) o solo uno.
    public function user()
{
    return $this->hasOne(User::class, 'persona_id');
}

    // Relación pivot con eventos y rol por evento
    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'event_persona_roles')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    // Helpers por rol en el pivot
    public function eventosComoAdmin()
    {
        return $this->eventos()->wherePivot('role', 'admin');
    }

    public function eventosComoSubadmin()
    {
        return $this->eventos()->wherePivot('role', 'subadmin');
    }

    public function eventosComoInvitado()
    {
        return $this->eventos()->wherePivot('role', 'invitado');
    }
    
}