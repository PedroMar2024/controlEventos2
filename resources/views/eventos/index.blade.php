@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Eventos</h1>

        @can('create', \App\Models\Evento::class)
            <a href="{{ route('eventos.create') }}"
               class="inline-flex items-center rounded bg-green-600 hover:bg-green-700 px-4 py-2 text-sm font-medium text-white">
               + Crear Evento
            </a>
        @endcan
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visibilidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($eventos ?? [] as $evento)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $evento->nombre }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ optional($evento->fecha_evento)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $evento->hora_inicio ? $evento->hora_inicio : '-' }}{{ $evento->hora_cierre ? ' — '.$evento->hora_cierre : '' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evento->ubicacion ?? $evento->localidad ?? 'N/A' }}</td>
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
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex gap-1 justify-end">
                                <!-- Botón Ver -->
                                <a href="{{ route('eventos.show', $evento->id) }}"
                                   class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-700 text-white rounded text-xs font-semibold">
                                    Ver
                                </a>
                                <!-- Botón Editar -->
                                @can('update', $evento)
                                    <a href="{{ route('eventos.edit', $evento->id) }}"
                                       class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-semibold">
                                        Editar
                                    </a>
                                @endcan
                                <!-- Botón Eliminar -->
                                @php
                                    $user = auth()->user();
                                    $esSuperadmin = $user && $user->hasRole('superadmin');
                                    $esAdmin = $user && $user->hasRole('admin_evento');
                                    $estadoPendiente = $evento->estado === 'pendiente';

                                    // Chequeo de admin del evento (ajusta si tu relación es distinta)
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
                                                class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-semibold @if(!$puedeEliminar) opacity-50 cursor-not-allowed @endif"
                                                @if(!$puedeEliminar) disabled @endif>
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay eventos disponibles.
                                @can('create', \App\Models\Evento::class)
                                    <a href="{{ route('eventos.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Crear el primero</a>
                                @endcan
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