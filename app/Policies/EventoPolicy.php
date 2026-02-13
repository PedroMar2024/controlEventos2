<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    // Superadmin pasa cualquier ability
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
    }

    // Helpers de identidad Persona y roles por evento (pivot)
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

    // Helpers de roles globales (Spatie)
    private function esAdminGlobal(User $user): bool
    {
        return $user->hasRole('admin_evento');
    }

    private function esSubadminGlobal(User $user): bool
    {
        return $user->hasRole('subadmin_evento');
    }

    // Listado: el controlador filtra por pertenencia (admin/subadmin o público)
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Ver un evento: solo admin o subadmin del evento (público NO otorga acceso aquí)
    public function view(User $user, Evento $evento): bool
    {
        return $this->esAdminEvento($user, $evento) || $this->esSubadminEvento($user, $evento);
    }

    // Crear evento: permitido al rol global admin_evento (y superadmin via before)
    public function create(User $user): bool
    {
        return $this->esAdminGlobal($user);
    }

    // Actualizar evento: admin global o admin/subadmin del evento
    public function update(User $user, Evento $evento): bool
    {
        // superadmin siempre (también pasa por before())
        if ($user->hasRole('superadmin')) return true;
    
        // admin del evento: solo si está pendiente
        return $evento->estado === 'pendiente' && $this->esAdminEvento($user, $evento);
    }
    
    public function delete(User $user, Evento $evento): bool
    {
        // Solo superadmin, y NO si está aprobado (en curso/publicado)
        return $user->hasRole('superadmin') && $evento->estado !== 'aprobado';
    }

    // Gestión de subadmins: admin del evento (o admin global)
    public function manageSubadmins(User $user, Evento $evento): bool
    {
        return $this->esAdminGlobal($user) || $this->esAdminEvento($user, $evento);
    }

    // Gestión de invitados: admin/subadmin del evento (o roles globales equivalentes)
    public function manageGuests(User $user, Evento $evento): bool
    {
        return $this->esAdminGlobal($user)
            || $this->esSubadminGlobal($user)
            || $this->esAdminEvento($user, $evento)
            || $this->esSubadminEvento($user, $evento);
    }

    // Aprobación / cancelación: solo superadmin (via before)
    public function approve(User $user, Evento $evento): bool { return false; }
    public function cancel(User $user, Evento $evento): bool { return false; }
}