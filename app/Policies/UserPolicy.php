<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Llave maestra: superadmin puede TODO con admins
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
    }

    /**
     * Por defecto, nadie puede crear/editar/borrar admins salvo superadmin
     */
    public function create(User $user)
    {
        return false;
    }

    public function update(User $user, User $target)
    {
        return false;
    }

    public function delete(User $user, User $target)
    {
        return false;
    }
}