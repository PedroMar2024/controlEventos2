<?php

namespace App\Providers;

use App\Models\Evento;
use App\Policies\EventoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // Registra la Policy de Evento
    protected $policies = [
        \App\Models\Evento::class => \App\Policies\EventoPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class, // <-- Línea nueva
    ];

    public function boot(): void
    {
        // Carga todas las policies registradas
        $this->registerPolicies();

        // No definas Gates aquí para Evento: la autorización se maneja en EventoPolicy.
        // El bypass de superadmin también queda cubierto por el método before() de la Policy.
    }
}