<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // LLAVE MAESTRA para superadmin
    public function before(User $user, $ability)
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
    }

    // Nadie salvo superadmin puede editar o borrar usuarios
    public function update(User $user, User $target) { return false; }
    public function delete(User $user, User $target) { return false; }
}