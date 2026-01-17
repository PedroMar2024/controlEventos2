@extends('layouts.app')

@section('title', 'Acceso Denegado')

@section('content')
    <h1>403 - Acceso Denegado</h1>
    <p>Lo sentimos, no tienes permiso para acceder a esta página o realizar esta acción.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary">Volver al Dashboard</a>
@endsection