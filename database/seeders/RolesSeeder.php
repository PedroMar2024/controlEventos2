<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('superadmin', 'web');

        $user = User::first(); // ajusta si querés por email/ID
        if ($user && !$user->hasRole('superadmin')) {
            $user->syncRoles(['superadmin']);
        }
    }
}