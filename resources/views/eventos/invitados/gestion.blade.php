@extends('layouts.app')

@section('title', "Gestionar invitados del evento: {$evento->nombre}")

@section('content')
    <h1 class="text-2xl font-bold mb-6">Gestión de invitados para "{{ $evento->nombre }}"</h1>
    <a href="{{ route('eventos.index') }}" class="text-blue-700 hover:underline mb-4 inline-block">← Volver a eventos</a>

    {{-- Mensajes flash --}}
    @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-6 rounded">
            {{ session('status') }}
        </div>
    @endif

    {{-- ========= PASO 1 ========= --}}
    <div class="mb-12 p-6 rounded-lg border bg-white shadow-sm">
        <div class="flex items-center mb-4">
            <span class="flex items-center justify-center bg-blue-600 text-white text-4xl font-extrabold rounded-full w-14 h-14 mr-6 shadow-lg border-4 border-blue-300 select-none">
                1
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 uppercase tracking-tight">
                Agendar invitados y pedir confirmación
            </h2>
        </div>
        <p class="mb-6 text-gray-500">Primero agregá todos los invitados. Después, <b>enviá la confirmación de asistencia</b> para saber quiénes realmente van a venir.</p>
        <div class="flex flex-col md:flex-row gap-6 md:gap-8 mb-8">
            {{-- -------- CARD 1: Invitación individual -------- --}}
            <div class="flex-1 bg-white border-2 border-blue-300 rounded-xl shadow-md p-6 flex flex-col items-stretch">
                <div class="flex items-center mb-4">
                    <span class="inline-block bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold mr-2">INVITACIÓN INDIVIDUAL</span>
                </div>
                <form method="POST" action="{{ route('eventos.invitados.agregar', $evento->id) }}" class="flex flex-col gap-2">
                    @csrf
                    <label for="email" class="font-semibold">Email del invitado</label>
                    <input type="email" name="email" id="email" required
                        class="py-2 px-4 border border-gray-300 rounded w-full mb-2"
                        placeholder="ejemplo@mail.com"/>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded font-semibold">
                        Agregar invitado
                    </button>
                </form>
            </div>
            {{-- -------- CARD 2: Invitación masiva XLS -------- --}}
            <div class="flex-1 bg-white border-2 border-emerald-400 rounded-xl shadow-md p-6 flex flex-col items-stretch">
                <div class="flex items-center mb-4">
                    <span class="inline-block bg-emerald-500 text-white px-3 py-1 rounded-full text-xs font-semibold mr-2">INVITACIÓN MASIVA XLS</span>
                </div>
                <form method="POST" action="{{ route('eventos.invitaciones.importarExcel', $evento->id) }}" enctype="multipart/form-data" class="flex flex-col gap-2">
                    @csrf
                    <label for="archivo_invitados" class="font-semibold">Importar invitados (Excel, xls/xlsx)</label>
                    <input type="file" name="archivo_invitados" id="archivo_invitados" accept=".xls,.xlsx" required
                        class="py-2 px-4 border border-gray-300 rounded w-full mb-2" />
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded font-semibold">
                        Importar invitados
                    </button>
                </form>
            </div>
        </div>

        {{-- Tabla de TODOS los invitados --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 shadow-sm border mt-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Confirmación enviada</th>
                        <th class="px-4 py-2 text-left">Confirmado</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invitados as $inv)
                        <tr>
                            <td class="px-4 py-2">{{ $inv->email }}</td>
                            <td class="px-4 py-2">
                                {!! $inv->enviada ? '<span class="text-green-700 font-bold">Sí</span>' : '<span class="text-yellow-700 font-bold">No</span>' !!}
                            </td>
                            <td class="px-4 py-2">
                                @if($inv->confirmado)
                                    <span class="text-green-700">Sí</span>
                                @elseif($inv->confirmado === 0)
                                    <span class="text-red-700">Rechazado</span>
                                @else
                                    <span class="text-gray-500">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 flex gap-2">
                                {{-- Solo botón ENVIAR si no fue enviada la confirmación --}}
                                @if(!$inv->enviada)
                                    <form method="POST" action="{{ route('eventos.invitaciones.enviar', ['evento' => $evento->id, 'invitacion' => $inv->id]) }}">
                                        @csrf
                                        <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Enviar pedido de confirmación</button>
                                    </form>
                                @endif

                                {{-- Botón ELIMINAR --}}
                                <form method="POST" action="{{ route('eventos.invitados.eliminar', [$evento->id, $inv->id]) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs"
                                        onclick="return confirm('¿Seguro que querés eliminar este invitado? Esta acción no se puede deshacer.')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-gray-600 text-center">No hay invitados cargados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Botón masivo para pedidos de confirmación --}}
        @if($invitados->where('enviada', false)->count())
        <form method="POST" action="{{ url('/eventos/' . $evento->id . '/invitaciones/enviar-masivo') }}" class="mt-6">
            @csrf
            <button type="submit" class="px-6 py-2 bg-green-700 hover:bg-green-800 text-white rounded font-semibold w-full md:w-auto">
                Enviar pedido de confirmación a TODOS los que faltan
            </button>
        </form>
        @endif
    </div>

    {{-- ========= PASO 2 ========= --}}
    <div class="p-6 rounded-lg border bg-white shadow-sm">
    <div class="flex items-center mb-4">
        <span class="flex items-center justify-center bg-indigo-700 text-white text-4xl font-extrabold rounded-full w-14 h-14 mr-6 shadow-lg border-4 border-indigo-300 select-none">
            2
        </span>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 uppercase tracking-tight">
            Enviar invitación definitiva sólo a los que confirmaron
        </h2>
    </div>
    <p class="mb-4 text-gray-500">
        Cuando algunos invitados CONFIRMEN, desde aquí podés mandarle la invitación definitiva o eliminarlos si no querés que participen.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 shadow-sm border mt-4 bg-gray-50">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">Apellido</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Situación</th>
                    <th class="px-4 py-2">Eliminar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($confirmadosNoInvitados as $inv)
                    <tr>
                        <td class="px-4 py-2">{{ $inv->nombre }}</td>
                        <td class="px-4 py-2">{{ $inv->apellido }}</td>
                        <td class="px-4 py-2">{{ $inv->email }}</td>
                        <td class="px-4 py-2">
                            @if($inv->invitacion_enviada)
                                <span class="text-green-700 font-bold">Enviada</span>
                            @else
                                <span class="text-yellow-700 font-bold">No enviada</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('eventos.invitados.eliminar', [$evento->id, $inv->id]) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs"
                                    onclick="return confirm('¿Seguro que querés eliminar este invitado? Esta acción no se puede deshacer.')">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-gray-600 text-center">Todavía nadie confirmó asistencia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($confirmadosNoInvitados->count())
        <form method="POST" action="{{ route('eventos.invitaciones.enviarFinales', $evento->id) }}" class="mt-6">
            @csrf
            <button type="submit" class="px-6 py-2 bg-indigo-700 hover:bg-indigo-900 text-white rounded font-semibold w-full md:w-auto">
                Enviar invitación definitiva a TODOS los confirmados sin invitación
            </button>
        </form>
    @endif
</div>
@endsection