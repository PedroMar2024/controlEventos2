{{-- Mensajes flash (status / success / warning / error) con auto-ocultado --}}
@foreach (['status' => 'green', 'success' => 'green', 'warning' => 'yellow', 'error' => 'red'] as $key => $color)
  @if (session($key))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition.opacity
         role="alert"
         aria-live="polite"
         class="mb-4 rounded-md bg-{{ $color }}-50 p-3 text-{{ $color }}-700 relative">
      {{ session($key) }}
      <button type="button"
              @click="show = false"
              class="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded hover:bg-{{ $color }}-100"
              aria-label="Cerrar">
        ×
      </button>
    </div>
  @endif
@endforeach

{{-- Errores de validación (persisten para corregir campos) --}}
@if ($errors->any())
  <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700" role="alert" aria-live="assertive">
    @foreach ($errors->all() as $message)
      <p>{{ $message }}</p>
    @endforeach
  </div>
@endif