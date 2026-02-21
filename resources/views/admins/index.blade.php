@extends('layouts.app')

@section('title','Administradores')
@section('content')
<div class="rounded-lg bg-white shadow p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-base font-semibold">Administradores de eventos</h1>
  </div>

  <div class="mt-3 overflow-x-auto">
  <table class="min-w-full divide-y divide-gray-200">
    <thead>
        <tr>
            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 bg-gray-50"></th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($admins as $admin)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $admin->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $admin->email }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex gap-1 justify-end">
                <!-- Botón Editar -->
                <a href="{{ route('admins.edit', $admin) }}"
                   class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-semibold">
                    Editar
                </a>
                <!-- Botón Ver eventos relacionados -->
                <a href="{{ route('admins.eventos', $admin) }}"
                class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs font-semibold">
                    Ver eventos
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
  </div>

  <div class="mt-4">{{ $admins->links() }}</div>
</div>
@endsection