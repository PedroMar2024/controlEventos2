@extends('layouts.app')

@section('title', "Gestionar invitados del evento: {$evento->nombre}")

@section('content')
    <h1 class="text-2xl font-bold mb-6">Gestión de invitados para "{{ $evento->nombre }}"</h1>
    <a href="{{ route('eventos.index') }}" class="text-blue-700 hover:underline mb-4 inline-block">← Volver a eventos</a>

    {{-- Mensajes flash --}}
    @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-6 rounded">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:gap-8 mb-8">
        {{-- Formulario de Agregar invitado --}}
        <form method="POST" action="{{ route('eventos.invitados.agregar', $evento->id) }}" class="mb-4 md:mb-0 flex flex-col gap-2 w-full md:w-1/2 bg-gray-50 border rounded p-4">
            @csrf
            <label for="email" class="font-semibold mb-1">Email del invitado</label>
            <input type="email" name="email" id="email" required
                   class="py-2 px-4 border border-gray-300 rounded w-full"
                   placeholder="ejemplo@mail.com"/>
            <button type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white px-5 py-2 rounded font-semibold mt-2">
                Agregar invitado
            </button>
        </form>
        {{-- Formulario de Importar invitados --}}
        <form method="POST" action="{{ route('eventos.invitaciones.importarExcel', $evento->id) }}" enctype="multipart/form-data" class="flex flex-col gap-2 w-full md:w-1/2 bg-gray-50 border rounded p-4">
            @csrf
            <label for="archivo_invitados" class="font-semibold mb-1">Importar invitados (Excel)</label>
            <input type="file" name="archivo_invitados" id="archivo_invitados" accept=".xls,.xlsx" required
                   class="py-2 px-4 border border-gray-300 rounded w-full" />
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded font-semibold mt-2">
                Importar invitados
            </button>
        </form>
    </div>

    {{-- Listado de invitados --}}
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 shadow-sm border">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Enviada</th>
                <th class="px-4 py-2 text-left">Confirmado</th>
                <th class="px-4 py-2 text-left">Datos completados</th>
                <th class="px-4 py-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invitados as $inv)
                <tr>
                    <td class="px-4 py-2">{{ $inv->email }}</td>
                    <td class="px-4 py-2">
                        @if($inv->enviada)
                            <span class="text-green-700">Sí</span>
                        @else
                            <span class="text-yellow-700">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($inv->confirmado)
                            <span class="text-green-700 font-bold">Confirmado</span>
                        @elseif($inv->confirmado === 0)
                            <span class="text-red-700 font-bold">Rechazado</span>
                        @else
                            <span class="text-gray-500">Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($inv->datos_completados)
                            <span class="text-green-700">Sí</span>
                        @else
                            <span class="text-gray-500">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 flex gap-2">
                        {{-- Acción individual: enviar notificación --}}
                        @if(!$inv->enviada)
                        <form method="POST" action="{{ route('eventos.invitaciones.enviar', ['evento' => $evento->id, 'invitacion' => $inv->id]) }}">
                            @csrf
                            <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Enviar</button>
                        </form>
                        @endif

                        {{-- BOTÓN ELIMINAR INVITADO --}}
                        <form method="POST" action="{{ route('eventos.invitados.eliminar', [$evento->id, $inv->id]) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs"
                                onclick="return confirm('¿Seguro que querés eliminar este invitado? Esta acción no se puede deshacer.')">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-gray-600 text-center">No hay invitados cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{-- Acciones masivas/Finales --}}
    <div class="flex flex-col md:flex-row gap-4 mt-8 justify-end">
    <form method="POST" action="{{ url('/eventos/' . $evento->id . '/invitaciones/enviar-masivo') }}">
    @csrf
    <button type="submit" class="px-5 py-2 bg-green-700 hover:bg-green-800 text-white rounded font-semibold text-base">
        
        Enviar notificaciones pendientes
    </button>
    </form>
        <form method="POST" action="{{ route('eventos.invitaciones.enviarFinales', $evento->id) }}">
            @csrf
            <button type="submit"
                class="px-5 py-2 bg-green-700 hover:bg-green-800 text-white rounded font-semibold text-base">
                Enviar invitaciones a confirmados
            </button>
        </form>
    </div>
@endsection