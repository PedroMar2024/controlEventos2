@extends('layouts.app')

@section('title', 'Equipo del Evento')

@section('content')
  @php
    use App\Models\Persona;
    $admin   = optional($admin ?? null);
    $count   = $count ?? 0;
    $limit   = 10;
    // Buscar persona solo si viene el email por GET (desde el buscador)
    $personaBuscada = null;
    if(request('buscar_email')) {
        $personaBuscada = Persona::where('email', request('buscar_email'))->first();
    }
  @endphp

  <div class="mx-auto max-w-3xl py-8">
    <div class="relative overflow-hidden rounded-2xl shadow-lg border bg-gradient-to-br from-indigo-50 to-white">
      <!-- Cabecera -->
      <div class="px-6 py-5 border-b bg:white/70 backdrop-blur">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">
            Equipo del Evento: {{ $evento->nombre }}
          </h1>
          <span class="text-sm text-gray-600">
            Subadmins: <strong>{{ $count }}</strong> / {{ $limit }}
          </span>
        </div>
        <p class="mt-1 text-sm text-gray-600">
          Admin principal: {{ $admin?->nombre ?: '—' }} ({{ $admin?->email }})
        </p>
      </div>

      <!-- Cuerpo -->
      <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Listado -->
        <div class="md:col-span-2 space-y-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Subadministradores</p>

            @if($subadmins->count() > 0)
              <ul class="divide-y divide-gray-200 rounded-lg border bg-white">
                @foreach ($subadmins as $p)
                  <li class="flex items-center justify-between px-4 py-3">
                    <div>
                      <p class="text-sm font-semibold text-gray-900">{{ $p->nombre ?? $p->email }}</p>
                      <p class="text-xs text-gray-600">{{ $p->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('eventos.equipo.subadmins.destroy', [$evento, $p]) }}">
                      @csrf
                      @method('DELETE')
                      <button type="submit"
                              class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Quitar
                      </button>
                    </form>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-sm text-gray-600">Aún no hay subadmins asignados.</p>
            @endif
          </div>

          <!-- Alta de subadmin -->
          <div class="mt-6">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Agregar subadmin</p>

            @if ($count < $limit)
              {{-- PASO 1: buscar persona por email --}}
              <form method="GET" action="{{ route('eventos.equipo.index', $evento) }}" class="mb-4">
                  <div class="flex gap-2">
                      <input type="email" name="buscar_email"
                             value="{{ request('buscar_email') }}"
                             placeholder="Email del subadmin"
                             required
                             class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                      <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm">
                        Buscar
                      </button>
                  </div>
              </form>

              {{-- PASO 2: si ya se buscó un email, mostrar el form adecuado --}}
              @if(request('buscar_email'))
                <form method="POST" action="{{ route('eventos.equipo.subadmins.store', $evento) }}" class="space-y-3">
                  @csrf
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                      <label class="block text-xs text-gray-600 mb-1">Nombre</label>
                      <input type="text" name="nombre"
                             value="{{ old('nombre', $personaBuscada->nombre ?? '') }}"
                             {{ $personaBuscada ? 'readonly' : 'required' }}
                             class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                      @error('nombre')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                      @enderror
                    </div>

                    <div>
                      <label class="block text-xs text-gray-600 mb-1">Apellido</label>
                      <input type="text" name="apellido"
                             value="{{ old('apellido', $personaBuscada->apellido ?? '') }}"
                             {{ $personaBuscada ? 'readonly' : 'required' }}
                             class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                      @error('apellido')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                      @enderror
                    </div>

                    <div>
                      <label class="block text-xs text-gray-600 mb-1">DNI</label>
                      <input type="text" name="dni"
                             value="{{ old('dni', $personaBuscada->dni ?? '') }}"
                             {{ $personaBuscada ? 'readonly' : 'required' }}
                             class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                      @error('dni')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                      @enderror
                    </div>

                    <div>
                      <label class="block text-xs text-gray-600 mb-1">Email</label>
                      <input type="email" name="email"
                             value="{{ old('email', $personaBuscada->email ?? request('buscar_email')) }}"
                             required {{ $personaBuscada ? 'readonly' : '' }}
                             class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                      @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                      @enderror
                    </div>

                  </div>

                  <button type="submit"
                          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Agregar subadmin
                  </button>
                </form>
              @endif
            @else
              <p class="text-sm text-gray-600">Se alcanzó el límite de {{ $limit }} subadmins para este evento.</p>
            @endif
          </div>
        </div>

        <!-- Lateral -->
        <div class="md:col-span-1">
          <div class="h-full rounded-xl border bg-white p-4 relative">
            <div class="absolute -left-3 top-6 w-6 h-6 rounded-full bg-white border"></div>
            <div class="absolute -left-3 bottom-6 w-6 h-6 rounded-full bg-white border"></div>

            <p class="text-xs uppercase tracking-wide text-gray-500">Evento</p>
            <p class="text-sm font-semibold text-gray-900">{{ $evento->nombre }}</p>

            <div class="mt-4 space-y-2">
              <a href="{{ route('eventos.show', $evento) }}"
                 class="inline-flex w-full items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Volver al evento
              </a>
              <a href="{{ route('eventos.index') }}"
                 class="inline-flex w-full items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Volver al listado
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Pie -->
      <div class="px-6 py-4 border-t bg-white/70">
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-500">ID Evento: {{ $evento->id }}</p>
          <p class="text-xs text-gray-500">Creado: {{ optional($evento->created_at)->format('d/m/Y H:i') }}</p>
        </div>
      </div>
    </div>
  </div>
@endsection