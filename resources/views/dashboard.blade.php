@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-lg bg-white shadow p-6">
            <h2 class="text-lg font-semibold">Bienvenido</h2>
            <p class="mt-2 text-sm text-gray-600">Sesión iniciada como <strong>{{ auth()->user()->email }}</strong></p>
            <p class="mt-1 text-sm text-gray-500">Roles: {{ auth()->user()->roles->pluck('name')->join(', ') }}</p>
        </div>

        @hasanyrole('superadmin|admin_evento|subadmin_evento')
        <div class="rounded-lg bg-white shadow p-6">
            <h2 class="text-lg font-semibold">Gestión de Eventos</h2>
            <p class="mt-2 text-sm text-gray-600">Crear, editar y administrar eventos.</p>
            <a href="{{ route('eventos.index') }}"
               class="mt-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
               Ir a Eventos
            </a>
        </div>
        @endhasanyrole

        @role('invitado')
        <div class="rounded-lg bg-white shadow p-6">
            <h2 class="text-lg font-semibold">Eventos Públicos</h2>
            <p class="mt-2 text-sm text-gray-600">Consulta eventos abiertos y elige opciones disponibles.</p>
            <a href="{{ route('events.index') }}"
               class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
               Ver Eventos
            </a>
        </div>
        @endrole
    </div>
@endsection