@extends('layouts.app')

@section('title','Editar Administrador')
@section('content')
<div class="rounded-lg bg-white shadow p-6 max-w-xl">
  <h1 class="text-base font-semibold">Editar administrador de eventos</h1>

  <form method="POST" action="{{ route('admins.update', $user) }}" class="mt-4 space-y-4">
    @csrf
    @method('PUT')
    <div>
      <label class="block text-sm font-medium text-gray-700">Nombre</label>
      <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
             class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Email</label>
      <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
             class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Contraseña (dejar vacío para no cambiar)</label>
      <input type="password" name="password"
             class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-3">
      <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
        Guardar cambios
      </button>
      <a href="{{ route('admins.index') }}" class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
        Cancelar
      </a>
    </div>
  </form>
</div>
@endsection