{{-- resources/views/admin/tickets/solicitudes/index.blade.php --}}
@extends('layouts.admin') {{-- Cambia según tu layout --}}
@section('content')
<h1>Bandeja de Solicitudes de Entradas</h1>
<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>ID</th>
            <th>Evento</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Cantidad</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
@foreach ($solicitudes as $s)
        <tr>
            <td>{{ $s->id }}</td>
            <td>
                {{ optional($s->evento)->nombre ?? "SIN EVENTO" }}
            </td>
            <td>{{ $s->nombre }}</td>
            <td>{{ $s->email }}</td>
            <td>{{ $s->cantidad }}</td>
            <td>{{ $s->estado ?? 'pendiente' }}</td>
            <td>
                @if (($s->estado ?? 'pendiente') == 'pendiente')
                <form action="{{ route('admin.tickets.solicitudes.aprobar', $s->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">Aprobar</button>
                </form>
                @endif
                {{-- Aquí más adelante: eliminar, reenviar... --}}
            </td>
        </tr>
@endforeach
    </tbody>
</table>
{!! $solicitudes->links() !!} {{-- Paginador --}}
@endsection