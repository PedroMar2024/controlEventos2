@extends('layouts.app')

@section('title','Eventos de admin')
@section('content')
<div class="rounded-lg bg-white shadow p-6">
  <h1 class="text-base font-semibold">Eventos de {{ $user->name ?? $user->email }}</h1>

  <div class="mt-3 overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Evento</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @forelse($eventos as $ev)
          <tr>
            <td class="px-4 py-2 text-sm text-gray-900">{{ $ev->nombre }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ optional($ev->fecha_evento)->format('d/m/Y') ?: '—' }}</td>
            <td class="px-4 py-2 text-sm">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                {{ $ev->estado === 'aprobado' ? 'bg-green-100 text-green-700' :
                   ($ev->estado === 'finalizado' ? 'bg-gray-100 text-gray-700' : 'bg-yellow-100 text-yellow-700') }}">
                {{ ucfirst($ev->estado ?? 'pendiente') }}
              </span>
            </td>
            <td class="px-4 py-2 text-sm">
              <div class="flex justify-end gap-2">
                <a href="{{ route('eventos.show', $ev->id) }}" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">Ver</a>
                {{-- Superadmin puede editar/eliminar (delete sujeto a policy delete) --}}
                <a href="{{ route('eventos.edit', $ev->id) }}" class="px-2 py-1 rounded bg-indigo-600 text-white">Editar</a>
                <form method="POST" action="{{ route('eventos.destroy', $ev->id) }}"
                      onsubmit="return confirm('¿Eliminar este evento?')">
                  @csrf @method('DELETE')
                  <button class="px-2 py-1 rounded bg-red-600 text-white">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Sin eventos</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $eventos->links() }}</div>
</div>
@endsection