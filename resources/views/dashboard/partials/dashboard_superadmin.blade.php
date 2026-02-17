@php
    use Carbon\Carbon;

    // Traer los 5 primeros administradores vinculados a eventos
    $primerosAdmins = \App\Models\Persona::whereIn('id',
        \DB::table('event_persona_roles')
            ->where('role', 'admin')
            ->distinct()
            ->pluck('persona_id')
            ->take(5)
    )->get();
    $totalAdminsEvento = \DB::table('event_persona_roles')
        ->where('role', 'admin')
        ->distinct('persona_id')
        ->count('persona_id');

    // Traer todos los eventos, calcular días faltantes y filtrar a solo los próximos (o hoy)
    $primerosEventos = \App\Models\Evento::orderBy('fecha_evento','asc')
        ->get()
        ->map(function($ev) {
            $ev->dias_faltan = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($ev->fecha_evento)->startOfDay(), false);
            return $ev;
        })
        ->filter(function($ev) {
            return $ev->dias_faltan >= 0;
        })
        ->sortBy('dias_faltan')
        ->take(5)
        ->values();

    $totalEventos = \App\Models\Evento::count();
    $fmtFecha = fn($ev) => optional($ev->fecha_evento)->format('d/m/Y') ?: '—';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

    <!-- Card: MIS ADMINISTRADORES -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col">
        <div class="flex justify-between items-center mb-2">
            <span class="text-2xl text-blue-700 font-extrabold">Administradores</span>
            <span class="text-lg font-semibold text-blue-900">{{ $totalAdminsEvento }}</span>
        </div>
        <ul class="flex flex-col gap-2 mb-2">
            @forelse($primerosAdmins as $admin)
                <li class="flex items-center bg-blue-50 rounded px-2 py-1">
                    <span class="text-sm font-medium text-gray-800 truncate text-left">
                        {{ $admin->nombre ?? '' }} {{ $admin->apellido ?? '' }}
                    </span>
                </li>
            @empty
                <li class="py-2 text-xs text-gray-400 text-center">Sin administradores vinculados</li>
            @endforelse
        </ul>
        <a href="{{ route('admins.index') }}"
           class="mt-auto text-xs font-medium px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors text-center self-end w-full">
           Gestionar
        </a>
    </div>

    <!-- Card: PRÓXIMOS EVENTOS -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col">
        <div class="flex justify-between items-center mb-2">
            <span class="text-2xl text-indigo-700 font-extrabold">Próximos eventos</span>
            <span class="text-lg font-semibold text-indigo-900">{{ $totalEventos }}</span>
        </div>
        <ul class="flex flex-col gap-2 mb-2">
            @forelse($primerosEventos as $ev)
                <li class="flex items-center justify-between bg-indigo-50 rounded px-2 py-1">
                    <span class="text-sm font-medium text-gray-900 truncate text-left">{{ $ev->nombre }}</span>
                    <div class="flex flex-col items-end min-w-[85px]">
                        <span class="text-sm font-mono text-gray-700">
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
                <li class="py-2 text-xs text-gray-400 text-center">No hay eventos próximos.</li>
            @endforelse
        </ul>
        <a href="{{ route('eventos.index') }}"
           class="mt-auto text-xs font-medium px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors text-center self-end w-full">
           Gestionar
        </a>
    </div>
</div>