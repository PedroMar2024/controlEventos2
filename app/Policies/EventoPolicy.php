<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    // Superadmin pasa cualquier ability (incluida 'delete')
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

    private function esAdminGlobal(User $user): bool
    {
        return $user->hasRole('admin_evento');
    }

    private function esSubadminGlobal(User $user): bool
    {
        return $user->hasRole('subadmin_evento');
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Evento $evento): bool
    {
        return $this->esAdminEvento($user, $evento) || $this->esSubadminEvento($user, $evento);
    }

    public function create(User $user): bool
    {
        return $this->esAdminGlobal($user);
    }

    // Admin del evento: solo edita mientras 'pendiente' (superadmin via before)
    public function update(User $user, Evento $evento): bool
    {
        return $evento->estado === 'pendiente' && $this->esAdminEvento($user, $evento);
    }

    // Eliminar: solo superadmin (admins no pueden)
    public function delete(User $user, Evento $evento): bool
    {
        return $user->hasRole('superadmin');
    }

    public function manageSubadmins(User $user, Evento $evento): bool
    {
        return $this->esAdminGlobal($user) || $this->esAdminEvento($user, $evento);
    }

    public function manageGuests(User $user, Evento $evento): bool
    {
        return $this->esAdminGlobal($user)
            || $this->esSubadminGlobal($user)
            || $this->esAdminEvento($user, $evento)
            || $this->esSubadminEvento($user, $evento);
    }

    // Aprobación / volver a pendiente: solo superadmin (pasa por before)
    public function approve(User $user, Evento $evento): bool { return false; }
    public function cancel(User $user, Evento $evento): bool { return false; }
}