<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Throwable;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Informar inicio
        $this->command->info('Iniciando RolesAndPermissionsSeeder');

        // Limpiar caché de permisos/roles
        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->command->info('Cache de permisos limpiada');
        }

        // Crear roles (solo si no existen)
        $roles = ['admin','user','client'];
        foreach ($roles as $r) {
            $role = Role::firstOrCreate(['name' => $r]);
            $this->command->info("Rol asegurado: {$role->name}");
        }

        // Usuarios de prueba reproducibles
        $users = [
            ['email' => 'admin@example.com',  'name' => 'Admin Demo',  'password' => bcrypt('password'), 'role' => 'admin'],
            ['email' => 'user@example.com',   'name' => 'User Demo',   'password' => bcrypt('secret'),   'role' => 'user'],
            ['email' => 'client@example.com', 'name' => 'Client Demo', 'password' => bcrypt('secret'),   'role' => 'client'],
        ];

        foreach ($users as $uData) {
            $u = User::firstOrCreate(
                ['email' => $uData['email']],
                ['name' => $uData['name'], 'password' => $uData['password']]
            );

            $this->command->info("Usuario asegurado: {$u->email} (id={$u->id})");

            // Intentar asignar rol y capturar excepción si falla
            try {
                if (! $u->hasRole($uData['role'])) {
                    $u->assignRole($uData['role']);
                    $this->command->info("Rol '{$uData['role']}' asignado a {$u->email}");
                } else {
                    $this->command->info("Usuario {$u->email} ya tiene rol '{$uData['role']}'");
                }
            } catch (Throwable $e) {
                $this->command->error("Error asignando rol a {$u->email}: ".$e->getMessage());
            }
        }

        $this->command->info('Seeder finalizado');
    }
}