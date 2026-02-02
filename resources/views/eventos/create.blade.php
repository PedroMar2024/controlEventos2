@extends('layouts.app')

@section('title', 'Crear Evento')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="rounded-lg bg-white shadow">
            <div class="px-6 py-5 border-b border-gray-200">
                <h1 class="text-2xl font-bold">Crear nuevo evento</h1>
                <p class="mt-1 text-sm text-gray-600">Completa los datos del evento y asigna un administrador por email.</p>
            </div>

            <div class="p-6">
                @if($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                        <h2 class="text-sm font-semibold text-red-700">Hay errores en el formulario:</h2>
                        <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('eventos.store') }}" class="space-y-8" id="create-event-form">
                    @csrf

                    <div class="rounded-md border border-gray-200 p-4">
                        <h2 class="text-sm font-semibold text-gray-800">Administrador del evento</h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Ingresa el email. Si existe, se autocompletan y se bloquean Nombre y DNI. Si no, completa esos datos mínimos.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Email (único)</label>
                                <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                       placeholder="admin@ejemplo.com">
                                @error('admin_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                <p id="admin_email_status" class="mt-1 text-xs"></p>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" name="admin_nombre" id="admin_nombre" value="{{ old('admin_nombre') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                       placeholder="Nombre del administrador">
                                @error('admin_nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">DNI</label>
                                <input type="text" name="admin_dni" id="admin_dni" value="{{ old('admin_dni') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                       placeholder="DNI">
                                @error('admin_dni') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre del evento</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                   placeholder="Ej. Expo Innovación 2026">
                            @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado</label>
                            <select name="estado"
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="pendiente"  {{ old('estado') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                                <option value="aprobado"   {{ old('estado') === 'aprobado'   ? 'selected' : '' }}>Aprobado</option>
                                <option value="finalizado" {{ old('estado') === 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            </select>
                            @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Fecha del evento</label>
                            <input type="date" name="fecha_evento" value="{{ old('fecha_evento') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('fecha_evento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hora de inicio</label>
                            <input type="time" name="hora_inicio" value="{{ old('hora_inicio') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('hora_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hora de cierre</label>
                            <input type="time" name="hora_cierre" value="{{ old('hora_cierre') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('hora_cierre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ubicación</label>
                            <input type="text" name="ubicacion" id="ubicacion" value="{{ old('ubicacion') }}"
                                   autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('ubicacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Localidad</label>
                            <input type="text" name="localidad" id="localidad" value="{{ old('localidad') }}"
                                   autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('localidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provincia</label>
                            <input type="text" name="provincia" id="provincia" value="{{ old('provincia') }}"
                                   autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('provincia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Capacidad y precio del evento eliminados (capacidad se deriva de los tickets, precio por ticket) --}}

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="publico" name="publico" value="1" {{ old('publico') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                            <label for="publico" class="ml-2 text-sm text-gray-700">Evento público</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="reingreso" name="reingreso" value="1" {{ old('reingreso') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                            <label for="reingreso" class="ml-2 text-sm text-gray-700">Permitir reingreso</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="descripcion" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                                  placeholder="Detalles del evento, agenda, notas, etc.">{{ old('descripcion') }}</textarea>
                        @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @include('eventos._tickets')

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Crear Evento
                        </button>
                        <a href="{{ route('eventos.index') }}"
                           class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
      async function lookupPersonaByEmail(email) {
        const status = document.getElementById('admin_email_status');
        const nombre = document.getElementById('admin_nombre');
        const dni    = document.getElementById('admin_dni');
        const localidad = document.getElementById('localidad');
        const provincia = document.getElementById('provincia');

        status.textContent = '';
        nombre.disabled = false;
        dni.disabled = false;

        if (!email) return;

        status.textContent = 'Buscando…';
        try {
          const url = '{{ route('personas.byEmail') }}' + '?email=' + encodeURIComponent(email);
          const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
          const data = await res.json();

          if (data.found) {
            status.textContent = 'Persona: ' + (data.nombre ?? '') + (data.dni ? ' (DNI ' + data.dni + ')' : '');
            nombre.value = data.nombre ?? '';
            dni.value    = data.dni ?? '';
            nombre.disabled = true;
            dni.disabled    = true;

            // Limpia posibles autofills del navegador
            if (localidad) localidad.value = '';
            if (provincia) provincia.value = '';
          } else {
            status.textContent = 'No existe. Completa Nombre y DNI.';
            nombre.value = '';
            dni.value    = '';
            nombre.disabled = false;
            dni.disabled    = false;

            if (localidad) localidad.value = '';
            if (provincia) provincia.value = '';
          }
        } catch (e) {
          status.textContent = 'Error buscando email.';
        }
      }

      document.getElementById('admin_email').addEventListener('blur', (e) => {
        document.getElementById('create-event-form')?.setAttribute('autocomplete', 'off');
        lookupPersonaByEmail(e.target.value);
      });
    </script>
@endsection