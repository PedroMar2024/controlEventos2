<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Persona;

class EventoRolService
{
    public function asignarPersonaARolEnEvento(int $eventoId, array $personaData, string $rol): void
    {
        $evento = Evento::findOrFail($eventoId);

        $persona = Persona::firstOrCreate(
            ['email' => $personaData['email']],
            $personaData
        );

        // attach si no existe ya con ese rol
        $already = $evento->personas()
            ->wherePivot('role', $rol)
            ->where('personas.id', $persona->id)
            ->exists();

        if (!$already) {
            $evento->personas()->attach($persona->id, ['role' => $rol]);
        }
    }
}