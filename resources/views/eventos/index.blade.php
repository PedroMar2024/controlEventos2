@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Eventos</h1>

        @can('crear-eventos')
            <a href="{{ route('eventos.create') }}"
               class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
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
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('eventos.show', $evento->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver</a>
                                    @can('editar-eventos')
                                        <a href="{{ route('eventos.edit', $evento->id) }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">Editar</a>
                                    @endcan
                                    @can('eliminar-eventos')
                                        <form action="{{ route('eventos.destroy', $evento->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este evento?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Eliminar</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay eventos disponibles.
                                @can('crear-eventos')
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