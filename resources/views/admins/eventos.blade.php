@foreach($eventos as $evento)
<tr>
    <td class="px-6 py-4 text-gray-800">{{ $evento->nombre }}</td>
    <td class="px-6 py-4 text-gray-700">{{ $evento->fecha_evento }}</td>
    <td class="px-6 py-4 text-gray-700">
        <span class="px-2 py-1 rounded bg-gray-200 text-xs">{{ ucfirst($evento->pivot->role) }}</span>
    </td>
    <td class="px-6 py-4">
        <a href="{{ route('eventos.edit', $evento) }}"
          class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-semibold">
            Editar
        </a>
        <!-- Aquí podés sumar el resto de los botones de acciones según el rol -->
    </td>
</tr>
@endforeach