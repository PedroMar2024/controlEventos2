@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $globalRoles = $user->getRoleNames()->join(', ');
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

        {{-- Superadmin: todos los eventos (sin aprobar/cancelar) --}}
        @if($esSuperadmin)
        <div class="rounded-lg bg-white shadow p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">Todos los eventos (Superadmin)</h3>
                <a href="{{ route('eventos.index') }}"
                   class="text-sm px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                    Gestionar Eventos
                </a>
            </div>

            @if($eventosTodos->isEmpty())
              <p class="mt-2 text-sm text-gray-500">No hay eventos creados aún.</p>
            @else
              <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                      <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($eventosTodos as $ev)
                      <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $ev->nombre }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $fmtFecha($ev) }}</td>
                        <td class="px-4 py-2 text-sm">
                          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $ev->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                               ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $fmtEstado($ev) }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                          <div class="flex justify-end gap-2">
                            <a href="{{ route('eventos.show', $ev->id) }}"
                               class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                            <a href="{{ route('eventos.edit', $ev->id) }}"
                               class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">Editar</a>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
        </div>
        @endif

        {{-- Admin --}}
        @if($esSuperadmin || $eventosAdmin->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres ADMIN</h3>

            @if($eventosAdmin->isEmpty())
              <p class="mt-2 text-sm text-gray-500">No tienes eventos como admin.</p>
            @else
              <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                      <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($eventosAdmin as $ev)
                      <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $ev->nombre }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $fmtFecha($ev) }}</td>
                        <td class="px-4 py-2 text-sm">
                          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $ev->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                               ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $fmtEstado($ev) }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge['admin'] }}">Admin</span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                          <div class="flex justify-end gap-2">
                            <a href="{{ route('eventos.show', $ev->id) }}"
                               class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                            <a href="{{ route('eventos.edit', $ev->id) }}"
                               class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">Editar</a>
                            {{-- Removidos Aprobar/Cancelar --}}
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
        </div>
        @endif

        {{-- Subadmin --}}
        @if($esSuperadmin || $eventosSubadmin->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres SUBADMIN</h3>

            @if($eventosSubadmin->isEmpty())
              <p class="mt-2 text-sm text-gray-500">No tienes eventos como subadmin.</p>
            @else
              <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                      <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($eventosSubadmin as $ev)
                      <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $ev->nombre }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $fmtFecha($ev) }}</td>
                        <td class="px-4 py-2 text-sm">
                          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $ev->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                               ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $fmtEstado($ev) }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge['subadmin'] }}">Subadmin</span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                          <div class="flex justify-end gap-2">
                            <a href="{{ route('eventos.show', $ev->id) }}"
                               class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
        </div>
        @endif

        {{-- Invitado --}}
        @if($eventosInvitado->isNotEmpty())
        <div class="rounded-lg bg-white shadow p-6">
            <h3 class="text-base font-semibold">Eventos donde eres INVITADO</h3>
            <div class="mt-3 overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  @foreach($eventosInvitado as $ev)
                    <tr>
                      <td class="px-4 py-2 text-sm text-gray-900">{{ $ev->nombre }}</td>
                      <td class="px-4 py-2 text-sm text-gray-700">{{ $fmtFecha($ev) }}</td>
                      <td class="px-4 py-2 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                          {{ $ev->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                             ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                          {{ $fmtEstado($ev) }}
                        </span>
                      </td>
                      <td class="px-4 py-2 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge['invitado'] }}">Invitado</span>
                      </td>
                      <td class="px-4 py-2 text-sm">
                        <div class="flex justify-end gap-2">
                          <a href="{{ route('eventos.show', $ev->id) }}"
                             class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        </div>
        @endif
    </div>
@endsection