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
            // Forzamos acceso total para superadmin, logueamos para auditoría
            \Log::info("FORZADO: superadmin puede todo", [
                'user_id' => $user->id,
                'ability' => $ability,
                'referer' => request()->headers->get('referer'),
                'uri' => request()->getRequestUri(),
            ]);
            return true;
        }
    }

    // Extrae el persona_id asociado al usuario
    private function personaId(User $user): ?int
    {
        return optional($user->persona)->id;
    }

    // Chequea si el usuario es admin del evento (usa el rol real de tu tabla)
    private function esAdminEvento(User $user, Evento $evento): bool
    {
        $pid = $this->personaId($user);
        if (!$pid) return false;

        $result = $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'admin_evento') // <- AJUSTADO para tu sistema
            ->exists();

        \Log::info('POLICY EventoPolicy.esAdminEvento', [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'result' => $result,
        ]);

        return $result;
    }

    // Chequea si el usuario es subadmin del evento (rol de tu tabla)
    private function esSubadminEvento(User $user, Evento $evento): bool
    {
        $pid = $this->personaId($user);
        if (!$pid) return false;

        $result = $evento->personas()
            ->where('personas.id', $pid)
            ->wherePivot('role', 'subadmin_evento') // <- AJUSTADO
            ->exists();

        \Log::info('POLICY EventoPolicy.esSubadminEvento', [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'result' => $result,
        ]);

        return $result;
    }

    // Chequea si el usuario tiene rol global de admin_evento
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

    // Visualización: permitido si es admin o subadmin del evento
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

    // Crear evento: debe tener rol admin_evento global
    public function create(User $user): bool
    {
        $allowed = $this->esAdminGlobal($user);
        \Log::info("POLICY EventoPolicy.create", [
            'user_id' => $user->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

    // Actualizar solo si es admin del evento y el evento está pendiente
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

    // Eliminar: solo superadmin (pero admin del evento puede si está pendiente)
    public function delete(User $user, Evento $evento): bool
    {
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

    // ---- AJUSTE PARA ACCESO REAL ----
    public function manageGuests(User $user, Evento $evento): bool
    {
        $allowed = $user->hasRole('superadmin')
            || $this->esAdminEvento($user, $evento)
            || $this->esSubadminEvento($user, $evento);

        \Log::info("POLICY EventoPolicy.manageGuests", [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
            'allowed' => $allowed,
        ]);
        return $allowed;
    }

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