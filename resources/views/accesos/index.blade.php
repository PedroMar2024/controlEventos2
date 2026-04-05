@extends('layouts.app')

@section('title', 'Control de Acceso - Eventos')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Control de Acceso a Eventos</h1>
    
    <p class="mb-6 text-gray-600">Seleccioná el evento para gestionar los ingresos y salidas.</p>

    @if($eventos->isEmpty())
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
            No hay eventos disponibles para control de acceso.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($eventos as $evento)
                <div class="bg-white border rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <h3 class="text-xl font-bold mb-2">{{ $evento->nombre }}</h3>
                    <p class="text-gray-600 mb-1">
                        <strong>Fecha:</strong> {{ $evento->fecha_evento ? $evento->fecha_evento->format('d/m/Y') : 'Sin fecha' }}
                    </p>
                    <p class="text-gray-600 mb-1">
                        <strong>Ubicación:</strong> {{ $evento->ubicacion ?? 'Sin ubicación' }}
                    </p>
                    <p class="text-gray-600 mb-4">
                        <strong>Estado:</strong> 
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            {{ $evento->estado === 'aprobado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($evento->estado) }}
                        </span>
                    </p>
                    
                    <a href="{{ route('accesos.show', $evento->id) }}" 
                       class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center px-4 py-2 rounded font-semibold">
                        Control de Ingreso
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection