@extends('layouts.app')

@section('title','Administradores')
@section('content')
<div class="rounded-lg bg-white shadow p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-base font-semibold">Administradores de eventos</h1>
    <a href="{{ route('admins.create') }}" class="px-3 py-2 rounded bg-indigo-600 text-white text-sm">Nuevo admin</a>
  </div>

  <div class="mt-3 overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @foreach($admins as $u)
          <tr>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $u->name ?? optional($u->persona)->nombre ?? '—' }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ $u->email }}</td>
            <td class="px-4 py-2 text-sm">
              <div class="flex justify-end gap-2">
                <a href="{{ route('admins.events', $u) }}" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver eventos</a>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $admins->links() }}</div>
</div>
@endsection