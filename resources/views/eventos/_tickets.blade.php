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

<div class="mt-6 border rounded-md">
  <div class="flex items-center justify-between px-4 py-2 border-b">
    <h3 class="text-sm font-semibold text-gray-800">Tipos de entrada (máximo 5)</h3>
    <button type="button" id="add-ticket"
            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
      Agregar
    </button>
  </div>

  <div class="p-4" id="tickets-container">
    @foreach($rows as $idx => $t)
      <div class="ticket-row grid grid-cols-12 gap-3 items-end mb-3">
        <input type="hidden" name="tickets[{{ $idx }}][id]" value="{{ $t['id'] ?? '' }}">
        <input type="hidden" name="tickets[{{ $idx }}][_destroy]" value="0">

        <div class="col-span-4">
          <label class="block text-xs font-medium text-gray-700">Nombre</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[{{ $idx }}][nombre]" maxlength="100"
                 value="{{ $t['nombre'] ?? '' }}">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Precio</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[{{ $idx }}][precio]" type="number" step="0.01" min="0"
                 value="{{ isset($t['precio']) ? $t['precio'] : '' }}">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Cupo (opcional)</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[{{ $idx }}][cupo]" type="number" min="0"
                 value="{{ $t['cupo'] ?? '' }}">
        </div>

        <div class="col-span-1 flex items-center mt-6">
          <input class="rounded border-gray-300 text-blue-600"
                 type="checkbox" name="tickets[{{ $idx }}][activo]" value="1"
                 {{ !empty($t['activo']) ? 'checked' : '' }}>
          <label class="ml-2 text-xs text-gray-700">Activo</label>
        </div>

        <div class="col-span-1 mt-6">
          <button type="button"
                  class="remove-ticket inline-flex items-center rounded-md border px-2 py-1 text-xs text-red-700 border-red-300 hover:bg-red-50">
            Eliminar
          </button>
        </div>
      </div>
    @endforeach

    <p class="text-xs text-gray-500 mt-2">Sugerencias: General, VIP, Platea, Campo, Preferencial.</p>
  </div>
</div>

<script>
(function() {
  const container = document.getElementById('tickets-container');
  const addBtn = document.getElementById('add-ticket');

  function currentRows() {
    return container.querySelectorAll('.ticket-row:not([data-removed="1"])').length;
  }

  function makeRow(idx) {
    return `
      <div class="ticket-row grid grid-cols-12 gap-3 items-end mb-3">
        <input type="hidden" name="tickets[${idx}][id]" value="">
        <input type="hidden" name="tickets[${idx}][_destroy]" value="0">

        <div class="col-span-4">
          <label class="block text-xs font-medium text-gray-700">Nombre</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[${idx}][nombre]" maxlength="100">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Precio</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[${idx}][precio]" type="number" step="0.01" min="0">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Cupo (opcional)</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                 name="tickets[${idx}][cupo]" type="number" min="0">
        </div>

        <div class="col-span-1 flex items-center mt-6">
          <input class="rounded border-gray-300 text-blue-600"
                 type="checkbox" name="tickets[${idx}][activo]" value="1" checked>
          <label class="ml-2 text-xs text-gray-700">Activo</label>
        </div>

        <div class="col-span-1 mt-6">
          <button type="button"
                  class="remove-ticket inline-flex items-center rounded-md border px-2 py-1 text-xs text-red-700 border-red-300 hover:bg-red-50">
            Eliminar
          </button>
        </div>
      </div>
    `;
  }

  function disableRowInputs(row) {
    row.querySelectorAll('input,select,textarea').forEach(el => {
      const name = el.getAttribute('name') || '';
      if (!name.endsWith('[_destroy]')) {
        el.disabled = true;
        el.removeAttribute('required');
      }
    });
  }

  addBtn?.addEventListener('click', () => {
    if (currentRows() >= 5) {
      alert('Máximo 5 tipos de entrada');
      return;
    }
    const idx = Date.now(); // índice único
    container.insertAdjacentHTML('beforeend', makeRow(idx));
  });

  container?.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-ticket')) {
      const row = e.target.closest('.ticket-row');
      const destroy = row.querySelector('input[name$="[_destroy]"]');
      if (destroy) destroy.value = '1';
      row.setAttribute('data-removed', '1');
      disableRowInputs(row);
      row.style.display = 'none';
    }
  });

  // Antes de enviar el formulario: deshabilitar filas eliminadas o vacías,
  // para evitar que el navegador valide inputs ocultos/no focuseables.
  const form = container.closest('form');
  form?.addEventListener('submit', () => {
    container.querySelectorAll('.ticket-row').forEach(row => {
      const destroy = row.querySelector('input[name$="[_destroy]"]');
      const nombre  = row.querySelector('input[name$="[nombre]"]');
      const precio  = row.querySelector('input[name$="[precio]"]');

      const removed = row.getAttribute('data-removed') === '1';
      const empty = (!nombre || !nombre.value.trim()) && (!precio || !precio.value);

      if (removed || empty) {
        if (destroy) destroy.value = '1';
        disableRowInputs(row);
      }
    });
  });
})();
</script>