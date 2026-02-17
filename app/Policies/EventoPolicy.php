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
        // Forzamos que nunca tire excepción, ni siquiera si algo falla en el middleware
        \Log::info("FORZADO: superadmin puede todo", [
            'user_id' => $user->id,
            'ability' => $ability,
            'referer' => request()->headers->get('referer'),
            'uri' => request()->getRequestUri(),
        ]);
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

        $result = $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'admin')
            ->exists();

        \Log::info('POLICY EventoPolicy.esAdminEvento', [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'result' => $result,
        ]);

        return $result;
    }

    private function esSubadminEvento(User $user, Evento $evento): bool
    {
        $pid = $this->personaId($user);
        if (!$pid) return false;

        $result = $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'subadmin')
            ->exists();

        \Log::info('POLICY EventoPolicy.esSubadminEvento', [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'result' => $result,
        ]);

        return $result;
    }

    private function esAdminGlobal(User $user): bool
    {
        $result = $user->hasRole('admin_evento');
        \Log::info('POLICY EventoPolicy.esAdminGlobal', [
            'user_id' => $user->id,
            'result' => $result,
        ]);
        return $result;
    }

    private function esSubadminGlobal(User $user): bool
    {
        $result = $user->hasRole('subadmin_evento');
        \Log::info('POLICY EventoPolicy.esSubadminGlobal', [
            'user_id' => $user->id,
            'result' => $result,
        ]);
        return $result;
    }

    public function viewAny(User $user): bool
    {
        \Log::info("POLICY EventoPolicy.viewAny: acceso permitido", ['user_id' => $user->id]);
        return true;
    }

    public function view(User $user, Evento $evento): bool
    {
        $allowed = $this->esAdminEvento($user, $evento) || $this->esSubadminEvento($user, $evento);
        \Log::info("POLICY EventoPolicy.view", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    public function create(User $user): bool
    {
        $allowed = $this->esAdminGlobal($user);
        \Log::info("POLICY EventoPolicy.create", [
            'user_id' => $user->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    // Admin del evento: solo edita mientras 'pendiente' (superadmin via before)
    public function update(User $user, Evento $evento): bool
    {
        $allowed = $evento->estado === 'pendiente' && $this->esAdminEvento($user, $evento);
        \Log::info("POLICY EventoPolicy.update", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'evento_estado' => $evento->estado,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    // Eliminar: solo superadmin (admins no pueden; igual superadmin pasa before)
    public function delete(User $user, Evento $evento): bool
{
    // Admin del evento puede borrar mientras el evento esté pendiente
    $allowed = $evento->estado === 'pendiente' && $this->esAdminEvento($user, $evento);
    \Log::info("POLICY EventoPolicy.delete", [
        'user_id' => $user->id,
        'evento_id' => $evento->id,
        'evento_estado' => $evento->estado,
        'allowed' => $allowed,
    ]);
    return $allowed;
}

    public function manageSubadmins(User $user, Evento $evento): bool
    {
        $allowed = $this->esAdminGlobal($user) || $this->esAdminEvento($user, $evento);
        \Log::info("POLICY EventoPolicy.manageSubadmins", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    public function manageGuests(User $user, Evento $evento): bool
    {
        $allowed = $this->esAdminGlobal($user)
            || $this->esSubadminGlobal($user)
            || $this->esAdminEvento($user, $evento)
            || $this->esSubadminEvento($user, $evento);

        \Log::info("POLICY EventoPolicy.manageGuests", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    // Aprobación / volver a pendiente: solo superadmin (pasa por before)
    public function approve(User $user, Evento $evento): bool {
        \Log::info("POLICY EventoPolicy.approve", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => false,
        ]);
        return false;
    }

    public function cancel(User $user, Evento $evento): bool {
        \Log::info("POLICY EventoPolicy.cancel", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => false,
        ]);
        return false;
    }
}