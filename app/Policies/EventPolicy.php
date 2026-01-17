<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'subadmin', 'invitado']);
    }

    public function view(User $user, Event $event)
    {
        if ($event->estado === 'activo' || $user->hasAnyRole(['superadmin', 'admin', 'subadmin'])) {
            return true;
        }
        return false;
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'subadmin']);
    }

    public function update(User $user, Event $event)
    {
        return $user->id === $event->created_by || $user->hasAnyRole(['superadmin', 'admin']);
    }

    public function delete(User $user, Event $event)
    {
        return $user->id === $event->created_by || $user->hasRole('superadmin');
    }
}