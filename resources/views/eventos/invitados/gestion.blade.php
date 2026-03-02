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

    {{-- Botón para ENVIAR INVITACIONES PENDIENTES --}}
    <form method="POST" action="{{ route('eventos.invitaciones.enviarMasivo', ['evento' => $evento->id]) }}">
        @csrf
        <button type="submit"
            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded font-semibold text-base mb-6">
            Enviar invitaciones pendientes
        </button>
    </form>

    {{-- Listado de invitados --}}
    <table class="min-w-full divide-y divide-gray-200 mt-8">
        <thead>
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
                    <td class="px-4 py-2">
                        {{-- Ejemplo de acción individual --}}
                        @if(!$inv->enviada)
                        <form method="POST" action="{{ route('eventos.invitaciones.enviar', ['evento' => $evento->id, 'invitacion' => $inv->id]) }}">
                            @csrf
                            <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Enviar</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-gray-600 text-center">No hay invitados cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection