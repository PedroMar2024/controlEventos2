<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            RolesSeeder::class,

            // Si tienes otros seeders, agrégalos aquí debajo EN ESTE ORDEN:
            // 1) Roles/Permisos
            // 2) Usuarios (si no los creas en el seeder de roles)
            // 3) Datos de dominio (Eventos, etc.)
            // Ejemplos:
            // UserSeeder::class,
            // EventoSeeder::class,
        ]);
    }
}