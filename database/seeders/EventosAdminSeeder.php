<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Persona;
use App\Models\User;
use App\Models\Evento;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EventosAdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Asegura que el rol "admin_evento" exista
        $rolAdmin = Role::firstOrCreate(['name'=>'admin_evento', 'guard_name'=>'web']);

        $usuarios = [
            [
                'email' => 'felixfarias2011@gmail.com',
                'name'  => 'Felix Farias',
                'persona' => [
                    'nombre'   => 'Felix',
                    'apellido' => 'Farias',
                    'dni'      => '12345678',
                    'telefono' => '111222333',
                    'email'    => 'felixfarias2011@gmail.com',
                    'direccion'=> 'Calle 1',
                ],
            ],
            [
                'email' => 'felix.m.farias2011@gmail.com',
                'name'  => 'Felix M. Farias',
                'persona' => [
                    'nombre'   => 'Felix M.',
                    'apellido' => 'Farias',
                    'dni'      => '87654321',
                    'telefono' => '444555666',
                    'email'    => 'felix.m.farias2011@gmail.com',
                    'direccion'=> 'Calle 2',
                ],
            ]
        ];

        foreach ($usuarios as $uInfo) {
            // 2. Crear persona (si no existe)
            $persona = Persona::firstOrCreate(['email' => $uInfo['email']], $uInfo['persona']);

            // 3. Crear usuario asociado a persona
            $password = Hash::make('admin123');
            $user = User::firstOrCreate(
                ['email' => $uInfo['email']],
                [
                    'name'       => $uInfo['name'],
                    'password'   => $password,
                    'persona_id' => $persona->id
                ]
            );

            // 4. Asignar el rol "admin_evento" con Spatie
            if (!$user->hasRole('admin_evento')) {
                $user->assignRole($rolAdmin);
            }

            // 5. Crear 3 eventos y asignar persona como admin en cada uno
            for ($i=1; $i<=3; $i++) {
                $evento = Evento::create([
                    'nombre'        => "Evento {$i} de {$persona->nombre}",
                    'descripcion'   => "Evento generado por seeder",
                    'fecha_evento'  => now()->addDays($i)->format('Y-m-d'),
                    'hora_inicio'   => "10:00",
                    'hora_cierre'   => "18:00",
                    'localidad'     => "Localidad $i",
                    'provincia'     => "Provincia $i",
                    'ubicacion'     => "Dirección $i",
                    'estado'        => 'pendiente',
                    'reingreso'     => false,
                    'admin_persona_id' => $persona->id,
                ]);

                // Persona como admin en el evento (pivot event_persona_roles)
                $evento->personas()->attach($persona->id, ['role' => 'admin']);
            }

            // 6. Simular token reset link para el usuario
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $uInfo['email']],
                [
                    'token' => Hash::make(Str::random(30)),
                    'created_at' => now(),
                ]
            );
        }
    }
}