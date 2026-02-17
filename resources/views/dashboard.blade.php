@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
    @php
        $user = auth()->user();
    @endphp

    @if($user->hasRole('superadmin'))
        @include('dashboard.partials.dashboard_superadmin')
    @elseif($user->hasRole('admin_evento'))
        @include('dashboard.partials.dashboard_admin')
    @elseif($user->hasRole('subadmin_evento'))
        @include('dashboard.partials.dashboard_subadmin')
    @elseif($user->hasRole('invitado'))
        @includeIf('dashboard.partials.dashboard_invitado')
    @else
        <div class="p-8 text-center text-lg text-gray-500">
            No tenés acceso a ningún panel de gestión.
        </div>
    @endif
@endsection