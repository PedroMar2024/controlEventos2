@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $globalRoles = $user->getRoleNames()->join(', '); // solo superadmin, si lo tienes
        $personaId = optional($user->persona)->id;

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

        $esSuperadmin = $user->hasRole('superadmin');

        // Colores para los badges de rol
        $badge = [
            'admin'    => 'bg-emerald-100 text-emerald-700',
            'subadmin' => 'bg-indigo-100 text-indigo-700',
            'invitado' => 'bg-gray-100 text-gray-700',
        ];
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

        @if($esSuperadmin || $eventosAdmin->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres ADMIN</h3>
            @if($eventosAdmin->isEmpty())
                <p class="mt-2 text-sm text-gray-500">No tienes eventos como admin.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach($eventosAdmin as $ev)
                        <li class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $ev->nombre }}</span>
                                <span class="ml-2 text-xs text-gray-500">({{ ucfirst($ev->estado ?? 'pendiente') }})</span>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $badge['admin'] }}">Admin</span>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('eventos.show', $ev->id) }}"
                                   class="text-sm px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                                <a href="{{ route('eventos.edit', $ev->id) }}"
                                   class="text-sm px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">Editar</a>
                                <!-- Las siguientes acciones pasan por Policy -->
                                <form method="POST" action="{{ route('eventos.aprobar', $ev->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-sm px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700">
                                        Aprobar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('eventos.cancelar', $ev->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-sm px-2 py-1 rounded bg-yellow-600 text-white hover:bg-yellow-700">
                                        Cancelar
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endif

        @if($esSuperadmin || $eventosSubadmin->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres SUBADMIN</h3>
            @if($eventosSubadmin->isEmpty())
                <p class="mt-2 text-sm text-gray-500">No tienes eventos como subadmin.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach($eventosSubadmin as $ev)
                        <li class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $ev->nombre }}</span>
                                <span class="ml-2 text-xs text-gray-500">({{ ucfirst($ev->estado ?? 'pendiente') }})</span>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $badge['subadmin'] }}">Subadmin</span>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('eventos.show', $ev->id) }}"
                                   class="text-sm px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                                {{-- Acciones propias de subadmin (si existen) --}}
                                {{-- <a href="{{ route('eventos.guests', $ev->id) }}"
                                   class="text-sm px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">Invitados</a> --}}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endif

        @if($eventosInvitado->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres INVITADO</h3>
            <ul class="mt-3 space-y-2">
                @foreach($eventosInvitado as $ev)
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $ev->nombre }}</span>
                            <span class="ml-2 text-xs text-gray-500">({{ ucfirst($ev->estado ?? 'pendiente') }})</span>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $badge['invitado'] }}">Invitado</span>
                        </div>
                        <a href="{{ route('eventos.show', $ev->id) }}"
                           class="text-sm px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
@endsection