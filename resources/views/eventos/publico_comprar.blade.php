@extends('layouts.app')

@section('title', 'Comprar Entradas')

@section('content')
<div class="mx-auto max-w-xl">
    <div class="rounded-lg bg-white shadow p-6">
        <h1 class="text-xl font-bold mb-4">Comprar entradas para {{ $evento->nombre }}</h1>
        <p class="mb-6 text-sm text-gray-500">
            Fecha: {{ optional($evento->fecha_evento)->format('d/m/Y') }} | 
            Lugar: {{ $evento->ubicacion ?? '—' }} | 
            Precio: ${{ $evento->precio_evento ?? 'Consultar' }}
        </p>

        @if($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3">
                <ul class="text-sm text-red-700 pl-5 list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('eventos.publico.comprar.procesar', ['evento' => $evento->id]) }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">DNI</label>
                <input type="text" name="dni" value="{{ old('dni') }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                @error('dni') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Apellido</label>
                <input type="text" name="apellido" value="{{ old('apellido') }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                @error('apellido') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cantidad de entradas</label>
                <input type="number" name="cantidad" min="1" value="{{ old('cantidad', 1) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                @error('cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Solicitar Entradas
            </button>
        </form>
    </div>
</div>
@endsection