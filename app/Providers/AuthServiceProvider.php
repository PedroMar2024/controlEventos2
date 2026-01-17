<?php

namespace App\Providers;

use App\Models\Evento;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Evento::class => \App\Policies\EventoPolicy::class, // si luego usas policies
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Regla global: superadmin pasa cualquier autorización
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        // Gates mínimos (para no-superadmin), opcional:
        Gate::define('ver-evento', function ($user, Evento $evento) {
            if ($evento->publico) return true;
            $pid = $user->persona?->id;
            if (!$pid) return false;

            return $evento->personas()
                ->wherePivotIn('role', ['admin','subadmin'])
                ->where('personas.id', $pid)
                ->exists();
        });

        Gate::define('editar-evento', fn($user, Evento $evento) => Gate::check('ver-evento', $evento));
        Gate::define('eliminar-evento', fn($user, Evento $evento) => Gate::check('editar-evento', $evento));
    }
}