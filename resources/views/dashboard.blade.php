@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $globalRoles = $user->getRoleNames()->join(', ');
        $personaId = optional($user->persona)->id;

        // Totales para las cards resumen
        $totalAdmins = \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->count();
        $totalEventos = \App\Models\Evento::count();

        // Eventos por rol (pivote event_persona_roles)
        $eventosAdmin = \App\Models\Evento::with(['personas' => function ($q) use ($personaId) {
                $q->where('personas.id', $personaId);
            }])
            ->whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'admin');
            })
            ->orderBy('fecha_evento','desc')
            ->get();

        $eventosSubadmin = \App\Models\Evento::with(['personas' => function ($q) use ($personaId) {
                $q->where('personas.id', $personaId);
            }])
            ->whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'subadmin');
            })
            ->orderBy('fecha_evento','desc')
            ->get();

        $eventosInvitado = \App\Models\Evento::with(['personas' => function ($q) use ($personaId) {
                $q->where('personas.id', $personaId);
            }])
            ->whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'invitado');
            })
            ->orderBy('fecha_evento','desc')
            ->get();

        // Todos los eventos para superadmin (máximo 15 para vista)
        $esSuperadmin = $user->hasRole('superadmin');
        $eventosTodos = $esSuperadmin
            ? \App\Models\Evento::orderBy('fecha_evento','desc')->limit(15)->get()
            : collect();

        // Badges por rol
        $badge = [
            'admin'    => 'bg-emerald-100 text-emerald-700',
            'subadmin' => 'bg-indigo-100 text-indigo-700',
            'invitado' => 'bg-gray-100 text-gray-700',
        ];

        $fmtFecha = fn($ev) => optional($ev->fecha_evento)->format('d/m/Y') ?: '—';
        $fmtEstado = fn($ev) => ucfirst($ev->estado ?? 'pendiente');
        $estadoChip = function($ev) {
            return $ev->estado === 'aprobado'
                ? 'bg-green-100 text-green-700'
                : ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700');
        };
    @endphp

    <div class="grid grid-cols-1 gap-6">
        <div class="rounded-lg bg-white shadow p-6">
            <h2 class="text-lg font-semibold">Bienvenido</h2>
            <p class="mt-2 text-sm text-gray-600">
                Sesión iniciada como <strong>{{ $user->email }}</strong>
            </p>
            <p class="mt-1 text-sm text-gray-500">
                Rol global: {{ $globalRoles ?: '—' }}
            </p>
        </div>

        <!-- DASHBOARD CARDS RESUMEN -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Card MIS ADMINISTRADORES -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center justify-center text-center">
                <div class="text-5xl text-blue-600 font-black mb-2">{{ $totalAdmins }}</div>
                <div class="text-lg font-semibold mb-4">MIS ADMINISTRADORES</div>
                <a href="{{ route('admins.index') }}" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium">
                    Gestionar
                </a>
            </div>
            <!-- Card EVENTOS -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center justify-center text-center">
                <div class="text-5xl text-indigo-600 font-black mb-2">{{ $totalEventos }}</div>
                <div class="text-lg font-semibold mb-4">EVENTOS</div>
                <a href="{{ route('eventos.index') }}" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-medium">
                    Gestionar
                </a>
            </div>
        </div>

        {{-- (Resto de tu dashboard sigue igual: paneles, tablas y cards por roles) --}}
        {{-- ... (todo el código que ya tenés de las tablas/cards de eventos) ... --}}
    </div>
@endsection