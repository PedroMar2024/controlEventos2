@extends('layouts.app')  {{-- puedes seguir usando app porque ya no rompe en guest --}}

@section('title', 'Iniciar sesión')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-lg bg-white shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Iniciar sesión</h1>

        @if($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-4 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="ml-2 text-sm text-gray-700">Recordarme</label>
            </div>
            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Entrar
            </button>
        </form>
    </div>
</div>
@endsection