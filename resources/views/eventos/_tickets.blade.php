@php
  // Filas desde old() (si hubo validación) o BD. No agregar fila vacía por defecto.
  $rows = old('tickets', isset($evento)
    ? $evento->tickets->map(fn($t) => [
        'id'     => $t->id,
        'nombre' => $t->nombre,
        'precio' => $t->precio,
        'cupo'   => $t->cupo,
        'activo' => $t->activo,
      ])->toArray()
    : []
  );
@endphp

<div class="border bg-gray-100 rounded p-4 mb-5">
    <h3 class="font-bold mb-2">Tipos de entrada</h3>
    <table class="min-w-full text-sm">
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Cupo</th>
            <th>Activo</th>
            <th>Eliminar</th>
        </tr>
        </thead>
        <tbody>
        @foreach($evento->tiposEntrada as $i => $t)
            <tr>
                <td>
                    <input type="hidden" name="tickets[{{$i}}][id]" value="{{ $t->id }}">
                    <input type="text" name="tickets[{{$i}}][nombre]"
                           value="{{ old('tickets.'.$i.'.nombre', $t->nombre) }}"
                           required class="rounded border px-2 py-1 w-full" />
                </td>
                <td>
                    <input type="number" step="0.01"
                           name="tickets[{{$i}}][precio]"
                           value="{{ old('tickets.'.$i.'.precio', $t->precio) }}"
                           required class="rounded border px-2 py-1 w-full" />
                </td>
                <td>
                    <input type="number"
                           name="tickets[{{$i}}][cupo]"
                           value="{{ old('tickets.'.$i.'.cupo', $t->cupo) }}"
                           required class="rounded border px-2 py-1 w-full" />
                </td>
                <td class="text-center">
                    <input type="checkbox" name="tickets[{{$i}}][activo]" value="1"
                           {{ old('tickets.'.$i.'.activo', $t->activo) ? 'checked' : '' }}>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="tickets[{{$i}}][_destroy]" value="1" />
                </td>
            </tr>
        @endforeach
        {{-- Fila para agregar nuevo --}}
        <tr>
            <td>
                <input type="text" name="tickets[new][nombre]" value="" class="rounded border px-2 py-1 w-full" />
            </td>
            <td>
                <input type="number" step="0.01" name="tickets[new][precio]" value="" class="rounded border px-2 py-1 w-full" />
            </td>
            <td>
                <input type="number" name="tickets[new][cupo]" value="" class="rounded border px-2 py-1 w-full" />
            </td>
            <td class="text-center">
                <input type="checkbox" name="tickets[new][activo]" value="1" />
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>
    <small class="block text-gray-500 mt-2">
      Marcá "Eliminar" para borrar un tipo.<br>
      Dejá vacía la fila nueva si no querés agregar otro tipo.
    </small>
</div>