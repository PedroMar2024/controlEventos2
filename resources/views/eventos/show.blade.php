@extends('layouts.app')

@section('title', 'Entrada del Evento')

@section('content')
  @php
    $fecha   = optional($evento->fecha_evento)->format('d/m/Y');
    $inicio  = $evento->hora_inicio ? substr($evento->hora_inicio, 0, 5) : '—';
    $cierre  = $evento->hora_cierre ? substr($evento->hora_cierre, 0, 5) : '—';
    $estado  = ucfirst($evento->estado ?? 'pendiente');
    // $publico = $evento->publico ? 'Público' : 'Privado';   // REMOVIDO: no aplica al sistema
    $reing   = $evento->reingreso ? 'Con reingreso' : 'Sin reingreso';
    $admin   = optional($evento->adminPersona);

    // Tickets y capacidad derivada
    $tickets = $evento->tickets ?? collect();
    $capacidadDerivada = $tickets->where('activo', true)->sum(fn($t) => (int)($t->cupo ?? 0));
  @endphp

  <div class="mx-auto max-w-3xl py-8">
    <!-- Tarjeta estilo entrada -->
    <div class="relative overflow-hidden rounded-2xl shadow-lg border bg-gradient-to-br from-indigo-50 to-white">
      <!-- Cabecera -->
      <div class="px-6 py-5 border-b bg:white/70 backdrop-blur">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">{{ $evento->nombre }}</h1>
          <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                       {{ $evento->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                          ($evento->estado === 'finalizado' ? 'bg-gray-200 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
            {{ $estado }}
          </span>
        </div>
        <p class="mt-1 text-sm text-gray-600">{{ $evento->descripcion }}</p>
      </div>

      <!-- Cuerpo de la entrada -->
      <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Fecha</p>
              <p class="text-base font-semibold text-gray-900">{{ $fecha ?: '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Horario</p>
              <p class="text-base font-semibold text-gray-900">{{ $inicio }} - {{ $cierre }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Localidad</p>
              <p class="text-base font-semibold text-gray-900">{{ $evento->localidad ?: '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Provincia</p>
              <p class="text-base font-semibold text-gray-900">{{ $evento->provincia ?: '—' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Ubicación</p>
              <p class="text-base font-semibold text-gray-900">{{ $evento->ubicacion ?: '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Capacidad (derivada)</p>
              <p class="text-base font-semibold text-gray-900">{{ $capacidadDerivada }}</p>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 mt-2">
            {{-- REMOVIDO: Público/Privado --}}
            {{-- <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">{{ $publico }}</span> --}}
            <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">{{ $reing }}</span>
          </div>

          <!-- Tipos de entrada -->
          <div class="mt-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Tipos de entrada</p>
            @if($tickets->count() > 0)
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($tickets as $t)
                  <div class="rounded-lg border bg-white px-4 py-3 flex items-center justify-between">
                    <div>
                      <p class="text-sm font-semibold text-gray-900">{{ $t->nombre }}</p>
                      <p class="text-xs text-gray-500">
                        {{ $t->cupo ? ('Cupo: ' . $t->cupo) : 'Cupo: —' }}
                        @if(!$t->activo)
                          <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">Inactivo</span>
                        @endif
                      </p>
                    </div>
                    <p class="text-sm font-bold text-emerald-700">${{ number_format((float)$t->precio, 2, ',', '.') }}</p>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-sm text-gray-600">Sin tipos de entrada configurados.</p>
            @endif
          </div>
        </div>

        <!-- Lateral tipo “talón” -->
        <div class="md:col-span-1">
          <div class="h-full rounded-xl border bg-white p-4 relative">
            <div class="absolute -left-3 top-6 w-6 h-6 rounded-full bg-white border"></div>
            <div class="absolute -left-3 bottom-6 w-6 h-6 rounded-full bg-white border"></div>

            <p class="text-xs uppercase tracking-wide text-gray-500">Administrador</p>
            <p class="text-sm font-semibold text-gray-900">
              {{ $admin?->nombre ?: '—' }}
            </p>
            {{-- REMOVIDO: email y DNI del admin --}}
            {{-- <p class="text-xs text-gray-600">{{ $admin?->email }}</p>
            @if($admin && $admin->dni)
              <p class="text-xs text-gray-600">DNI {{ $admin->dni }}</p>
            @endif --}}

            {{-- REMOVIDO: bloque de Estado en lateral (ya aparece arriba) --}}
            {{-- <div class="mt-4">
              <p class="text-xs uppercase tracking-wide text-gray-500">Estado</p>
              <p class="text-sm font-semibold text-gray-900">{{ $estado }}</p>
            </div> --}}

            <div class="mt-6 space-y-2">
            @can('update', $evento)
                      <a href="{{ route('eventos.edit', $evento->id) }}"
              class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
              Editar evento
            </a>
          @endcan
            

              @can('manageSubadmins', $evento)
                <a href="{{ route('eventos.equipo.index', $evento) }}"
                   class="inline-flex w-full items-center justify-center rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-900">
                  Gestionar equipo
                </a>
              @endcan

              <a href="{{ route('eventos.index') }}"
                 class="inline-flex w-full items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Volver al listado
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Pie con ID -->
      <div class="px-6 py-4 border-t bg:white/70">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">ID Evento: {{ $evento->id }}</p>
          <p class="text-xs text-gray-500">Creado: {{ optional($evento->created_at)->format('d/m/Y H:i') }}</p>
        </div>
      </div>
    </div>
  </div>
@endsection