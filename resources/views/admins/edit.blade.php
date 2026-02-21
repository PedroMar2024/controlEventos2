@extends('layouts.app')

@section('title','Editar Administrador')
@section('content')
<div class="rounded-lg bg-white shadow p-6 max-w-xl">
  <h1 class="text-base font-semibold">Editar administrador de eventos</h1>

  <!-- Form principal: editar datos de admin -->
  <form method="POST" action="{{ route('admins.update', $user) }}" class="mt-4 space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $user->persona->nombre ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Apellido</label>
        <input type="text" name="apellido" value="{{ old('apellido', $user->persona->apellido ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('apellido') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">DNI</label>
        <input type="text" name="dni" value="{{ old('dni', $user->persona->dni ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('dni') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 bg-gray-100"
            readonly>
        <small class="text-gray-500">Para cambiar el administrador asignado, usá la opción “Cambiar admin” en la gestión de eventos.</small>
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Contraseña (dejar vacío para no cambiar)</label>
        <input type="password" name="password"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <hr class="my-6">

    <div class="flex gap-3 mb-8">
      <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Guardar cambios
      </button>
      <a href="{{ route('admins.index') }}" class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
          Cancelar
      </a>
    </div>
  </form>

  <!-- Botón para cambiar admin, ahora fuera del form -->
  <div class="mb-4">
      <button
        type="button"
        onclick="document.getElementById('cambiar-admin-form').style.display = 'block';"
        class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
        Cambiar Administrador
      </button>
  </div>

  <!-- Formulario de cambio de admin, fuera del form principal -->
  <div id="cambiar-admin-form" style="display:none;" class="mt-6 border p-4 rounded bg-gray-50">
      <form method="POST" action="{{ route('eventos.cambiar-admin', $evento->id) }}">
          @csrf
          <h3 class="text-base font-semibold mb-2">Nuevo Administrador</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                  <label>Email</label>
                  <input type="email" name="admin_email" id="nuevo_admin_email" class="block w-full mt-1 rounded-md border-gray-300" required>
              </div>
              <div>
                  <label>Nombre</label>
                  <input type="text" name="admin_nombre" id="nuevo_admin_nombre" class="block w-full mt-1 rounded-md border-gray-300" required>
              </div>
              <div>
                  <label>Apellido</label>
                  <input type="text" name="admin_apellido" id="nuevo_admin_apellido" class="block w-full mt-1 rounded-md border-gray-300" required>
              </div>
              <div>
                  <label>DNI</label>
                  <input type="text" name="admin_dni" id="nuevo_admin_dni" class="block w-full mt-1 rounded-md border-gray-300" required>
              </div>
          </div>
          <div class="mt-4 flex gap-3">
              <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">Confirmar Cambio</button>
              <button type="button"
                  onclick="document.getElementById('cambiar-admin-form').style.display='none';"
                  class="bg-gray-200 rounded px-4 py-2 text-gray-700">Cancelar</button>
          </div>
      </form>
  </div>
</div>

<!-- JS de autocompletado y bloqueo de datos -->
<script>
  async function lookupPersonaNuevoAdmin(input) {
    const email = input.value;
    const nombre = document.getElementById('nuevo_admin_nombre');
    const apellido = document.getElementById('nuevo_admin_apellido');
    const dni = document.getElementById('nuevo_admin_dni');

    // Reset fields and unlock
    nombre.value = "";
    apellido.value = "";
    dni.value = "";
    nombre.readOnly = false;
    apellido.readOnly = false;
    dni.readOnly = false;

    if (!email) return;

    try {
      const url = '{{ route('personas.byEmail') }}' + '?email=' + encodeURIComponent(email);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
      const data = await res.json();
      if (data.found) {
        nombre.value = data.nombre ?? '';
        apellido.value = data.apellido ?? '';
        dni.value = data.dni ?? '';
        nombre.readOnly = true;
        apellido.readOnly = true;
        dni.readOnly = true;
      }
    } catch(e) {
      console.log('Error buscando persona', e);
    }
  }

  document.getElementById('nuevo_admin_email').addEventListener('blur', function() {
    lookupPersonaNuevoAdmin(this);
  });
</script>
@endsection