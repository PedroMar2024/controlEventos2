@extends('layouts.app')
@section('title', 'Confirmar invitación')
@section('content')
    <h1 class="text-2xl font-bold mb-4">Confirmación de invitación al evento: {{ $invitacion->evento->nombre ?? '' }}</h1>
    <p>Tu mail: <strong>{{ $invitacion->email }}</strong></p>
    <form method="POST" action="{{ route('invitacion.confirmar.procesar') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $invitacion->token }}">
        
        @if(!$invitacion->datos_completados)
            <div class="mb-4">
                <label for="nombre" class="block font-semibold mb-1">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="border rounded px-3 py-1 w-72" required>
            </div>
            <div class="mb-4">
                <label for="apellido" class="block font-semibold mb-1">Apellido</label>
                <input type="text" name="apellido" id="apellido" class="border rounded px-3 py-1 w-72" required>
            </div>
            <div class="mb-4">
                <label for="dni" class="block font-semibold mb-1">DNI</label>
                <input type="text" name="dni" id="dni" class="border rounded px-3 py-1 w-72" required>
            </div>
        @endif

        <button type="submit" name="accion" value="confirmar" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded font-semibold">Confirmar asistencia</button>
        <button type="submit" name="accion" value="rechazar" class="bg-red-700 hover:bg-red-800 text-white px-5 py-2 rounded font-semibold ml-4">Rechazar invitación</button>
    </form>
@endsection