@extends('layouts.app')

@section('title', 'Editar Evento')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="rounded-lg bg-white shadow">
            <div class="px-6 py-5 border-b border-gray-200">
                <h1 class="text-2xl font-bold">Editar evento</h1>
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

                <form method="POST" action="{{ route('eventos.update', $evento->id) }}" class="space-y-8">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre del evento</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $evento->nombre) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado</label>
                            @php $estado = old('estado', $evento->estado); @endphp
                            <select name="estado"
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="pendiente"  {{ $estado === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                                <option value="aprobado"   {{ $estado === 'aprobado'   ? 'selected' : '' }}>Aprobado</option>
                                <option value="finalizado" {{ $estado === 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            </select>
                            @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="descripcion" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                        >{{ old('descripcion', $evento->descripcion) }}</textarea>
                        @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Fecha del evento</label>
                            <input type="date" name="fecha_evento" value="{{ old('fecha_evento', optional($evento->fecha_evento)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('fecha_evento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hora de inicio</label>
                            <input type="time" name="hora_inicio"
                                   value="{{ old('hora_inicio', $evento->hora_inicio ? substr($evento->hora_inicio, 0, 5) : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('hora_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hora de cierre</label>
                            <input type="time" name="hora_cierre"
                                   value="{{ old('hora_cierre', $evento->hora_cierre ? substr($evento->hora_cierre, 0, 5) : '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            @error('hora_cierre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Ubicación</label>
                        <input type="text" name="ubicacion" id="ubicacion"
                               value="{{ $evento->ubicacion ?? '' }}"
                               autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('ubicacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Localidad</label>
                        <input type="text" name="localidad" id="localidad"
                               value="{{ $evento->localidad ?? '' }}"
                               autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('localidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Provincia</label>
                        <input type="text" name="provincia" id="provincia"
                               value="{{ $evento->provincia ?? '' }}"
                               autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('provincia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="publico" name="publico" value="1" {{ old('publico', $evento->publico) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                            <label for="publico" class="ml-2 text-sm text-gray-700">Evento público</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="reingreso" name="reingreso" value="1" {{ old('reingreso', $evento->reingreso) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                            <label for="reingreso" class="ml-2 text-sm text-gray-700">Permitir reingreso</label>
                        </div>
                    </div>

                    @include('eventos._tickets', ['evento' => $evento])

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Guardar cambios
                        </button>
                        <a href="{{ route('eventos.index') }}" class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection