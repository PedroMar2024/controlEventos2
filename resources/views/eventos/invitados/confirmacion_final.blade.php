@extends('layouts.app')

@section('title', 'Confirmación registrada')

@section('content')
    <div class="w-full max-w-2xl mx-auto mt-12 p-8 bg-white rounded shadow text-center">
        <h1 class="text-2xl font-bold mb-4 text-green-700">¡Respuesta registrada!</h1>
        <p class="mb-6">
            Tu confirmación fue registrada correctamente.<br>
            ¡Gracias por responder!
        </p>
        <button onclick="window.close()" class="px-6 py-2 bg-gray-400 text-white rounded">Finalizar</button>
        <!-- O simplemente:
        <p class="mt-6 text-gray-500">Ahora podés cerrar esta ventana.</p>
        -->
    </div>
@endsection