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

                            @role('superadmin')
                                <select name="estado"
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-blue-600 focus:ring-blue-600">
                                    <option value="pendiente"  {{ $estado === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                                    <option value="aprobado"   {{ $estado === 'aprobado'   ? 'selected' : '' }}>Aprobado</option>
                                    <option value="finalizado" {{ $estado === 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                                </select>
                                @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 px-3 py-2 text-gray-700">
                                    {{ ucfirst($estado) }}
                                </div>
                                <!-- El admin_evento nunca puede enviar otro estado (sólo el mismo valor del evento) -->
                                <input type="hidden" name="estado" value="{{ $estado }}">
                            @endrole
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
                   <!-- Bloque de Admin Actual -->
<div class="border bg-gray-100 rounded p-4 mb-5">
    <h3 class="font-bold mb-2">Administrador del evento</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label>Email:</label>
            <input type="email" name="admin_email" id="admin_email" value="{{ $evento->adminPersona->email ?? '' }}" class="w-full rounded border" readonly>
        </div>
        <div>
            <label>Nombre:</label>
            <input type="text" name="admin_nombre" id="admin_nombre" value="{{ $evento->adminPersona->nombre ?? '' }}" class="w-full rounded border">
        </div>
        <div>
            <label>Apellido:</label>
            <input type="text" name="admin_apellido" id="admin_apellido" value="{{ $evento->adminPersona->apellido ?? '' }}" class="w-full rounded border">
        </div>
        <div>
            <label>DNI:</label>
            <input type="text" name="admin_dni" id="admin_dni" value="{{ $evento->adminPersona->dni ?? '' }}" class="w-full rounded border">
        </div>
    </div>

    {{-- Bloque visible solo para superadmin --}}
    @role('superadmin')
        <small class="block text-gray-500 mt-2">
            Podés cambiar los datos del administrador actual.<br>
            Si querés poner un nuevo admin, usá el botón de abajo.
        </small>
        <!-- Botón para abrir modal de cambio de admin -->
        <button type="button" onclick="abrirModalAdmin()" class="mt-4 bg-indigo-600 text-white rounded px-4 py-2">
            Cambiar Administrador
        </button>
    @endrole
</div>

<!-- Ventana flotante para cambio de admin -->
<div id="cambiar-admin-modal" style="display:none;" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md relative">
        <button type="button" onclick="cerrarModalAdmin()"
            class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-xl">×</button>
        <h3 class="text-lg font-bold mb-3">Cambiar Administrador</h3>
        <label>Email del nuevo admin:</label>
        <input type="email" id="nuevo_admin_email" class="w-full rounded border mb-2" onblur="buscarNuevoAdmin(event)" autocomplete="off">
        <div id="nuevo-admin-campos" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label>Nombre:</label>
                <input type="text" id="nuevo_admin_nombre" class="w-full rounded border" readonly>
            </div>
            <div>
                <label>Apellido:</label>
                <input type="text" id="nuevo_admin_apellido" class="w-full rounded border" readonly>
            </div>
            <div>
                <label>DNI:</label>
                <input type="text" id="nuevo_admin_dni" class="w-full rounded border" readonly>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="button" class="bg-blue-600 text-white rounded px-4 py-2" onclick="actualizarAdminEnForm()">Confirmar</button>
            <button type="button" class="bg-gray-200 text-gray-800 rounded px-4 py-2" onclick="cerrarModalAdmin()">Cancelar</button>
        </div>
        <small id="nuevo-admin-msg" class="block text-xs text-gray-500 mt-2"></small>
    </div>
</div>
<script>
// Para evitar problemas de opacidad y posición
function abrirModalAdmin() {
    document.getElementById('cambiar-admin-modal').style.display = 'flex';
}
function cerrarModalAdmin() {
    document.getElementById('cambiar-admin-modal').style.display = 'none';
}
async function buscarNuevoAdmin(event) {
    const email = document.getElementById('nuevo_admin_email').value;
    const nombre = document.getElementById('nuevo_admin_nombre');
    const apellido = document.getElementById('nuevo_admin_apellido');
    const dni = document.getElementById('nuevo_admin_dni');
    const msg = document.getElementById('nuevo-admin-msg');
    if (!email) return;

    try {
        const res = await fetch(`/personas/by-email?email=${encodeURIComponent(email)}`);
        const data = await res.json();
        if (data && data.persona) {
            nombre.value = data.persona.nombre;
            apellido.value = data.persona.apellido;
            dni.value = data.persona.dni;
            nombre.readOnly = true;
            apellido.readOnly = true;
            dni.readOnly = true;
            msg.innerText = "El email ya existe, se va a usar este admin (los datos no se pueden editar).";
        } else {
            nombre.value = "";
            apellido.value = "";
            dni.value = "";
            nombre.readOnly = false;
            apellido.readOnly = false;
            dni.readOnly = false;
            msg.innerText = "El email no existe en el sistema, completá los datos y se creará una nueva ficha.";
        }
    } catch {
        msg.innerText = "Error conectando con el sistema.";
    }
}
function actualizarAdminEnForm() {
    document.getElementById('admin_email').value = document.getElementById('nuevo_admin_email').value;
    document.getElementById('admin_nombre').value = document.getElementById('nuevo_admin_nombre').value;
    document.getElementById('admin_apellido').value = document.getElementById('nuevo_admin_apellido').value;
    document.getElementById('admin_dni').value = document.getElementById('nuevo_admin_dni').value;
    cerrarModalAdmin();
}
</script>
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