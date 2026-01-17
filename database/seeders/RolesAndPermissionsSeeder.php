<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Persona;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos base del sistema
        $permisos = [
            // Eventos
            'ver-eventos',
            'crear-eventos',
            'editar-eventos',
            'eliminar-eventos', // SOLO superadmin y admin_evento global (y admin del evento por pivot)

            // Acciones del evento
            'aprobar-eventos',
            'cancelar-eventos',
            'duplicar-eventos',

            // Invitados / opciones básicas
            'ver-opciones',
            'elegir-opciones',

            // Usuarios
            'ver-usuarios',
            'crear-usuarios',
            'editar-usuarios',
            'eliminar-usuarios',
            'asignar-roles',

            // Reportes
            'ver-reportes',
            'generar-reportes',
            'exportar-reportes',

            // Configuración
            'ver-configuracion',
            'editar-configuracion',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p], ['guard_name' => 'web']);
        }

        // Roles globales
        $superadmin     = Role::firstOrCreate(['name' => 'superadmin']);
        $adminEvento    = Role::firstOrCreate(['name' => 'admin_evento']);
        $subadminEvento = Role::firstOrCreate(['name' => 'subadmin_evento']);
        $invitado       = Role::firstOrCreate(['name' => 'invitado']);

        // Asignación de permisos a roles
        // superadmin: todo
        $superadmin->syncPermissions(Permission::all());

        // admin_evento: casi todo (incluye eliminar-eventos global)
        $adminEvento->syncPermissions([
            'ver-eventos','crear-eventos','editar-eventos','eliminar-eventos',
            'aprobar-eventos','cancelar-eventos','duplicar-eventos',
            'ver-opciones','elegir-opciones',
            'ver-usuarios','crear-usuarios','editar-usuarios','asignar-roles',
            'ver-reportes','generar-reportes','exportar-reportes',
            'ver-configuracion','editar-configuracion',
        ]);

        // subadmin_evento: igual que admin_evento PERO SIN eliminar-eventos
        $subadminEvento->syncPermissions([
            'ver-eventos','crear-eventos','editar-eventos',
            'aprobar-eventos','cancelar-eventos','duplicar-eventos',
            'ver-opciones','elegir-opciones',
            'ver-usuarios','crear-usuarios','editar-usuarios',
            'ver-reportes','generar-reportes','exportar-reportes',
            'ver-configuracion',
        ]);

        // invitado: acceso mínimo
        $invitado->syncPermissions([
            'ver-eventos',
            'ver-opciones','elegir-opciones',
        ]);

        // Personas + Users demo (se crean primero como personas)
        $seed = [
            ['email' => 'superadmin@example.com', 'name' => 'Super Admin', 'role' => 'superadmin'],
            ['email' => 'admin_evento@example.com', 'name' => 'Admin Evento', 'role' => 'admin_evento'],
            ['email' => 'subadmin_evento@example.com', 'name' => 'Subadmin Evento', 'role' => 'subadmin_evento'],
            ['email' => 'invitado@example.com', 'name' => 'Invitado', 'role' => 'invitado'],
        ];

        foreach ($seed as $s) {
            // Persona primero
            $persona = Persona::firstOrCreate(
                ['email' => $s['email']],
                [
                    'nombre'   => $s['name'],
                    'apellido' => '',
                    'email'    => $s['email'],
                ]
            );

            // User vinculado a persona
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name'       => $s['name'],
                    'password'   => bcrypt('password'),
                    'persona_id' => $persona->id,
                ]
            );

            if (!$user->persona_id) {
                $user->update(['persona_id' => $persona->id]);
            }

            if (!$user->hasRole($s['role'])) {
                $user->assignRole($s['role']);
            }
        }
    }
}