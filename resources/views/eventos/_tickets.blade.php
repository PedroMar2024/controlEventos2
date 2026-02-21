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
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
                 name="tickets[{{ $idx }}][nombre]" maxlength="100"
                 value="{{ $t['nombre'] ?? '' }}">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Precio</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
                 name="tickets[{{ $idx }}][precio]" type="number" step="0.01" min="0"
                 value="{{ isset($t['precio']) ? $t['precio'] : '' }}">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Cupo (opcional)</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
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

<!-- MODAL de confirmación de ticket -->
<div id="ticket-confirm-modal" class="hidden fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
  <div class="bg-white rounded shadow p-6">
    <h2 class="text-lg font-semibold mb-2">Ticket confirmado</h2>
    <p class="mb-4">¿Deseás agregar otro tipo de ticket o guardar el evento?</p>
    <div class="flex gap-4">
      <button type="button" id="modal-add-ticket" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Agregar otro ticket</button>
      <button type="button" id="modal-save-event" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Guardar evento</button>
    </div>
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
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
                 name="tickets[${idx}][nombre]" maxlength="100">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Precio</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
                 name="tickets[${idx}][precio]" type="number" step="0.01" min="0">
        </div>

        <div class="col-span-3">
          <label class="block text-xs font-medium text-gray-700">Cupo (opcional)</label>
          <input class="mt-1 block w-full rounded-md border-gray-300 text-sm ticket-input"
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
        el.readOnly = true;
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

  // Detectar Enter en cualquiera de los inputs de tickets
  container?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.closest('.ticket-row')) {
      e.preventDefault();

      // Confirmar la fila de ticket (readonly)
      const row = e.target.closest('.ticket-row');
      disableRowInputs(row);

      // Mostrar el modal
      mostrarVentanaDecision();
    }
  });

  // MODAL lógica
  function mostrarVentanaDecision() {
    const modal = document.getElementById('ticket-confirm-modal');
    modal.classList.remove('hidden');
    document.getElementById('modal-add-ticket').focus();
  }

  document.getElementById('modal-add-ticket').addEventListener('click', () => {
    document.getElementById('ticket-confirm-modal').classList.add('hidden');
    addBtn.click();
  });

  document.getElementById('modal-save-event').addEventListener('click', () => {
    document.getElementById('ticket-confirm-modal').classList.add('hidden');
    // Enviá el formulario principal
    const form = container.closest('form');
    form.submit();
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