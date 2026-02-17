@php
    use Carbon\Carbon;

    $user = auth()->user();
    $personaId = optional($user->persona)->id;

    // Traer eventos donde soy admin o subadmin
    $eventosAdmin = \App\Models\Evento::whereHas('personas', function ($q) use ($personaId) {
        $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'admin');
    })->get();

    $eventosSubadmin = \App\Models\Evento::whereHas('personas', function ($q) use ($personaId) {
        $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'subadmin');
    })->get();

    // Combinar, calcular días faltantes y filtrar solo los futuros o hoy
    $eventosAdministrador = $eventosAdmin->map(function($ev) {
        $ev->mi_rol = 'Admin';
        return $ev;
    })->merge(
        $eventosSubadmin->map(function($ev) {
            $ev->mi_rol = 'Subadmin';
            return $ev;
        })
    )->map(function($ev) {
        // Solo diferencia en días, enteros, negativo si ya pasó
        $ev->dias_faltan = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($ev->fecha_evento)->startOfDay(), false);
        return $ev;
    })
    // Solo los que faltan días o son hoy
    ->filter(function($ev){
        return $ev->dias_faltan >= 0;
    })
    ->sortBy('dias_faltan')->values();

    $totalEventosAdministrador = $eventosAdministrador->count();

    // Invitaciones
    $eventosInvitado = \App\Models\Evento::whereHas('personas', function ($q) use ($personaId) {
        $q->where('personas.id', $personaId)->where('event_persona_roles.role', 'invitado');
    })->orderBy('fecha_evento','asc')->get();

    $totalInvitaciones = $eventosInvitado->count();

    $fmtFecha = fn($ev) => optional($ev->fecha_evento)->format('d/m/Y') ?: '—';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

    <!-- Card "MIS EVENTOS" (Admin / Subadmin) -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col">
        <div class="flex justify-between items-center mb-2">
            <span class="text-2xl text-emerald-700 font-extrabold">Mis eventos</span>
            <span class="text-lg font-semibold text-emerald-900">{{ $totalEventosAdministrador }}</span>
        </div>
        <ul class="flex flex-col gap-2 mb-2">
            @forelse($eventosAdministrador as $ev)
                <li class="flex items-center justify-between bg-emerald-50 rounded px-2 py-1">
                    <div class="flex flex-col items-start">
                        <span class="text-sm font-medium text-gray-900">{{ $ev->nombre }}</span>
                        <span class="text-xs {{ $ev->mi_rol === 'Admin' ? 'text-emerald-700' : 'text-indigo-700' }} font-semibold">{{ $ev->mi_rol }}</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-lg font-mono text-gray-700">
                            @if($ev->dias_faltan === 0)
                                Hoy
                            @elseif($ev->dias_faltan === 1)
                                Falta 1 día
                            @elseif($ev->dias_faltan > 1)
                                Faltan {{ $ev->dias_faltan }} días
                            @endif
                        </span>
                        <span class="text-xs text-gray-400">{{ $fmtFecha($ev) }}</span>
                    </div>
                </li>
            @empty
                <li class="py-2 text-xs text-gray-400 text-center">No estás como admin ni subadmin en ningún evento próximo.</li>
            @endforelse
        </ul>
        <a href="{{ route('eventos.index') }}"
            class="mt-auto text-xs font-medium px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors text-center self-end w-full">
            Gestionar
        </a>
    </div>

    <!-- Card MIS INVITACIONES -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col">
        <div class="flex justify-between items-center mb-2">
            <span class="text-2xl text-yellow-700 font-extrabold">Mis invitaciones</span>
            <span class="text-lg font-semibold text-yellow-800">{{ $totalInvitaciones }}</span>
        </div>
        <ul class="flex flex-col gap-2 mb-2">
            @forelse($eventosInvitado as $ev)
                <li class="flex items-center justify-between bg-yellow-50 rounded px-2 py-1">
                    <span class="text-sm font-medium text-gray-900">{{ $ev->nombre }}</span>
                    <button class="ml-2 px-3 py-1 text-xs rounded bg-yellow-500 text-white hover:bg-yellow-600 transition-colors">
                        Ver
                    </button>
                </li>
            @empty
                <li class="py-2 text-xs text-gray-400 text-center">No fuiste invitado a ningún evento.</li>
            @endforelse
        </ul>
        <a href="{{ route('eventos.index') }}"
            class="mt-auto text-xs font-medium px-3 py-2 rounded bg-yellow-600 text-white hover:bg-yellow-700 transition-colors text-center self-end w-full">
            Gestionar
        </a>
    </div>
</div>