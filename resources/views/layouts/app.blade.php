<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Control de Eventos')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900">
<div id="app" class="min-h-full">
<header class="bg-white shadow">
    <nav x-data="{ open: false }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded bg-blue-600 text-white font-bold">CE</span>
                <span class="text-lg font-semibold">Control de Eventos</span>
            </a>

            <!-- Menú Desktop -->
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Dashboard</a>

                @auth
                    {{-- Enlace para Superadmin --}}
                    @role('superadmin')
                    <a href="{{ route('admins.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        Administradores
                    </a>
                    @endrole

                    @hasanyrole('superadmin|admin_evento|subadmin_evento')
                        <a href="{{ route('eventos.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Gestionar Eventos
                        </a>
                    @endhasanyrole
                    @role('invitado')
                        <a href="{{ route('events.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            Ver Eventos Públicos
                        </a>
                    @endrole
                @endauth

                <div class="flex items-center gap-3">
                    @auth
                        <div class="text-sm text-gray-500">
                            {{ auth()->user()->name ?? auth()->user()->email }}
                            <span class="ml-1 text-xs text-gray-400">
                                ({{ auth()->user()->roles->pluck('name')->join(', ') }})
                            </span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                                Cerrar sesión
                            </button>
                        </form>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Iniciar sesión
                        </a>
                    @endguest
                </div>
            </div>

            <!-- Hamburguesa para mobile -->
            <div class="flex md:hidden">
                <button @click="open = !open" type="button" class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menú Mobile (solo visible en mobile y cuando open==true) -->
        <div x-show="open" x-transition class="md:hidden" id="mobile-menu">
            <div class="mt-3 flex flex-col gap-2 pb-4 border-b">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded text-base font-medium text-gray-700 hover:bg-gray-100">Dashboard</a>

                @auth
                    @role('superadmin')
                        <a href="{{ route('admins.index') }}" class="block px-3 py-2 rounded text-base font-medium text-gray-700 hover:bg-gray-100">
                            Administradores
                        </a>
                    @endrole
                    @hasanyrole('superadmin|admin_evento|subadmin_evento')
                        <a href="{{ route('eventos.index') }}" class="block px-3 py-2 rounded text-base font-medium text-gray-700 hover:bg-gray-100">
                            Gestionar Eventos
                        </a>
                    @endhasanyrole
                    @role('invitado')
                        <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded text-base font-medium text-gray-700 hover:bg-gray-100">
                            Ver Eventos Públicos
                        </a>
                    @endrole

                    <div class="flex items-center gap-3 px-3 py-2">
                        <span class="text-sm text-gray-500">
                            {{ auth()->user()->name ?? auth()->user()->email }}
                            <span class="ml-1 text-xs text-gray-400">
                                ({{ auth()->user()->roles->pluck('name')->join(', ') }})
                            </span>
                        </span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="ml-2 inline-flex items-center rounded-md bg-red-600 px-2 py-1 text-sm font-medium text-white hover:bg-red-700">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 mt-2">
                        Iniciar sesión
                    </a>
                @endguest
            </div>
        </div>
    </nav>
</header>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-auto bg-white border-t">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 text-sm text-gray-500">
            © {{ date('Y') }} Control de Eventos
        </div>
    </footer>
</div>
</body>
</html>