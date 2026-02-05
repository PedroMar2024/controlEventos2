<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
    }

    private function personaId(User $user): ?int
    {
        return optional($user->persona)->id;
    }

    private function esAdminEvento(User $user, Evento $evento): bool
    {
        $pid = $this->personaId($user);
        if (!$pid) return false;

        return $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    private function esSubadminEvento(User $user, Evento $evento): bool
    {
        $pid = $this->personaId($user);
        if (!$pid) return false;

        return $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'subadmin')
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        return true; // El controlador se encarga de filtrar la lista por pertenencia
    }

    public function view(User $user, Evento $evento): bool
    {
        // SOLO pertenencia (admin o subadmin). 'publico' NO otorga acceso.
        return $this->esAdminEvento($user, $evento) || $this->esSubadminEvento($user, $evento);
    }

    public function create(User $user): bool { return false; }   // solo superadmin via before()
    public function update(User $user, Evento $evento): bool { return false; }
    public function delete(User $user, Evento $evento): bool { return false; }

    public function manageSubadmins(User $user, Evento $evento): bool
    {
        return $this->esAdminEvento($user, $evento);
    }

    public function manageGuests(User $user, Evento $evento): bool
    {
        return $this->esAdminEvento($user, $evento) || $this->esSubadminEvento($user, $evento);
    }

    public function approve(User $user, Evento $evento): bool { return false; }
    public function cancel(User $user, Evento $evento): bool { return false; }
}