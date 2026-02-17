@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Eventos</h1>
        {{-- Solo Admin y Superadmin pueden crear --}}
        @hasanyrole('superadmin|admin_evento')
            @can('create', \App\Models\Evento::class)
                <a href="{{ route('eventos.create') }}"
                   class="inline-flex items-center rounded bg-green-600 hover:bg-green-700 px-4 py-2 text-sm font-medium text-white">
                   + Crear Evento
                </a>
            @endcan
        @endhasanyrole
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visibilidad</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-center" colspan="2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @php
                        $user = auth()->user();
                        function rolUsuarioEnEvento($evento, $user) {
                            if (!$user || !$evento->relationLoaded('personas')) return '-';
                            $personaId = $user->persona_id ?? null;
                            $pivot = $evento->personas->firstWhere('id', $personaId)?->pivot?->role ?? null;
                            if ($pivot === 'admin') return 'Admin';
                            if ($pivot === 'subadmin') return 'Subadmin';
                            if ($pivot === 'invitado') return 'Invitado';
                            return $pivot ? ucfirst($pivot) : '-';
                        }
                    @endphp

                    @forelse($eventos ?? [] as $evento)
                        @php
                            $personaId = $user->persona_id ?? null;
                            $pivotPersona = $evento->personas->firstWhere('id', $personaId)?->pivot ?? null;
                            $rolEnEsteEvento = $pivotPersona?->role ?? null;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $evento->nombre }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ optional($evento->fecha_evento)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $evento->hora_inicio ? $evento->hora_inicio : '-' }}{{ $evento->hora_cierre ? ' — '.$evento->hora_cierre : '' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evento->ubicacion ?? $evento->localidad ?? 'N/A' }}</td>
                            <!-- Nueva columna ROL -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ rolUsuarioEnEvento($evento, $user) }}
                            </td>
                            <td class="px-6 py-4">
                                @switch($evento->estado)
                                    @case('aprobado')
                                        <span class="inline-flex items-center rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Aprobado</span>
                                        @break
                                    @case('finalizado')
                                        <span class="inline-flex items-center rounded bg-gray-200 px-2 py-1 text-xs font-medium text-gray-800">Finalizado</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Pendiente</span>
                                @endswitch
                            </td>
                            <td class="px-6 py-4">
                                @if($evento->publico)
                                    <span class="inline-flex items-center rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">Público</span>
                                @else
                                    <span class="inline-flex items-center rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800">Privado</span>
                                @endif
                            </td>
                            <!-- ACCIONES GRUPO 1: CRUD (Edit/Eliminar solo admin/superadmin) -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-row items-center gap-x-4 justify-end">
                                    <!-- Grupo 1: Botones CRUD todos mismo ancho -->
                                    <div class="flex flex-col gap-y-1 items-end">
                                        <a href="{{ route('eventos.show', $evento->id) }}"
                                            class="inline-flex items-center justify-center w-24 px-3 py-1 bg-blue-500 hover:bg-blue-700 text-white rounded text-xs font-semibold mb-1">
                                            Ver
                                        </a>
                                        @hasanyrole('superadmin|admin_evento')
                                            @can('update', $evento)
                                                <a href="{{ route('eventos.edit', $evento->id) }}"
                                                    class="inline-flex items-center justify-center w-24 px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-semibold mb-1">
                                                    Editar
                                                </a>
                                            @endcan
                                        @endhasanyrole
                                        @hasanyrole('superadmin|admin_evento')
                                            @php
                                                $esSuperadmin = $user && $user->hasRole('superadmin');
                                                $esAdmin = $user && $user->hasRole('admin_evento');
                                                $estadoPendiente = $evento->estado === 'pendiente';

                                                $esAdminEvento = false;
                                                if ($esAdmin && $evento->relationLoaded('personas')) {
                                                    $esAdminEvento = $evento->personas->where('pivot.role', 'admin')
                                                        ->where('id', $user->persona_id ?? -1)->isNotEmpty();
                                                }
                                                $puedeEliminar = $esSuperadmin || ($esAdminEvento && $estadoPendiente);
                                            @endphp
                                            @if($esSuperadmin || $esAdminEvento)
                                                <form method="POST" action="{{ route('eventos.destroy', $evento) }}"
                                                        onsubmit="return confirm('¿Seguro que desea eliminar este evento?');"
                                                        style="display:inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center justify-center w-24 px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-semibold @if(!$puedeEliminar) opacity-50 cursor-not-allowed @endif"
                                                            @if(!$puedeEliminar) disabled @endif>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        @endhasanyrole
                                    </div>
                                    <!-- Separador visual (opcional, podés eliminar si no lo querés) -->
                                    <div class="hidden md:block h-10 border-l border-gray-200 mx-1"></div>
                                    <!-- Grupo 2: Invitados y Equipo -->
                                    <div class="flex flex-col gap-y-1 items-end">
                                        @if($rolEnEsteEvento === 'admin' || $rolEnEsteEvento === 'subadmin' || $user->hasRole('superadmin'))
                                            <button type="button"
                                                    class="inline-flex items-center px-3 py-1 bg-purple-500 text-white rounded text-xs font-semibold opacity-90 cursor-not-allowed mb-1"
                                                    title="Pendiente de desarrollo">
                                                Agregar invitados
                                            </button>
                                        @endif

                                        @if($rolEnEsteEvento === 'admin' || $user->hasRole('superadmin'))
                                            <a href="{{ route('eventos.equipo.index', $evento->id) }}"
                                                class="inline-flex items-center px-3 py-1 bg-gray-700 hover:bg-gray-800 text-white rounded text-xs font-semibold">
                                                Gestionar equipo
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay eventos disponibles.
                                @hasanyrole('superadmin|admin_evento')
                                    @can('create', \App\Models\Evento::class)
                                        <a href="{{ route('eventos.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Crear el primero</a>
                                    @endcan
                                @endhasanyrole
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(isset($eventos) && method_exists($eventos, 'links'))
        <div class="mt-6">
            {{ $eventos->links() }}
        </div>
    @endif
@endsection