<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear los roles
        $roles = [
            'superadmin',
            'admin_evento',
            'subadmin_evento',
            'invitado'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Crear el usuario superadmin si NO existe
        $admin = User::firstOrCreate(
            [ 'email' => 'superadmin@admin.com' ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'), // ¡Podés cambiar el password!
                // Si tenés más campos obligatorios (persona_id, etc), agregalos acá
            ]
        );

        // 3. Asignar el rol superadmin
        $admin->syncRoles(['superadmin']);

        // (Opcional) Mensaje en consola
        $this->command->info('Roles creados y Superadmin configurado!');
    }
}