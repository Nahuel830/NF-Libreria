<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') — @endif{{ $nombreNegocio ?? 'NF Librería' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .barra-nf { background-color: var(--nf-principal); }
        .barra-nf .navbar-brand img { height: 32px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark barra-nf">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ auth()->check() ? route('inicio') : route('login') }}">
                <img src="{{ logo_url() }}" alt="Logo" height="32">
                {{ $nombreNegocio ?? 'NF Librería' }}
            </a>

            @auth
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Menú">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuPrincipal">
                    <ul class="navbar-nav me-auto">
                        @can('realizar-ventas')
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('ventas.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-cart"></i> Ventas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('ventas.nueva') ? 'active' : '' }}" href="{{ route('ventas.nueva') }}">Nueva venta</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('ventas.index', 'ventas.ver') ? 'active' : '' }}" href="{{ route('ventas.index') }}">Historial de ventas</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" href="{{ route('caja.mi-caja') }}">Caja</a></li>
                                    @can('ver-todas-las-ventas')
                                        <li><a class="dropdown-item {{ request()->routeIs('caja.historial') ? 'active' : '' }}" href="{{ route('caja.historial') }}">Historial de cajas</a></li>
                                    @endcan
                                    @can('gestionar-clientes')
                                        <li><a class="dropdown-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">Clientes</a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan

                        @canany(['gestionar-categorias', 'ver-productos', 'registrar-entradas', 'gestionar-proveedores'])
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('categorias.*', 'productos.*', 'entradas.*', 'inventario.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-boxes"></i> Inventario
                                </a>
                                <ul class="dropdown-menu">
                                    @can('gestionar-categorias')
                                        <li><a class="dropdown-item {{ request()->routeIs('categorias.*') ? 'active' : '' }}" href="{{ route('categorias.index') }}">Categorías</a></li>
                                    @endcan
                                    @can('ver-productos')
                                        <li><a class="dropdown-item {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ route('productos.index') }}">Productos</a></li>
                                    @endcan
                                    @can('registrar-entradas')
                                        <li><a class="dropdown-item {{ request()->routeIs('entradas.*') ? 'active' : '' }}" href="{{ route('entradas.index') }}">Entradas de mercadería</a></li>
                                    @endcan
                                    @can('gestionar-proveedores')
                                        <li><a class="dropdown-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" href="{{ route('proveedores.index') }}">Proveedores</a></li>
                                    @endcan
                                    @can('gestionar-stock')
                                        <li><a class="dropdown-item {{ request()->routeIs('inventario.stock-bajo') ? 'active' : '' }}" href="{{ route('inventario.stock-bajo') }}">Stock bajo</a></li>
                                        <li><a class="dropdown-item {{ request()->routeIs('inventario.conteo*') ? 'active' : '' }}" href="{{ route('inventario.conteo') }}">Conteo físico</a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany

                        @can('ver-reportes')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}"><i class="bi bi-bar-chart"></i> Reportes</a>
                            </li>
                        @endcan

                        @canany(['gestionar-usuarios', 'gestionar-configuracion', 'ver-auditoria'])
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('usuarios.*', 'configuracion.*', 'auditoria.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-gear"></i> Administración
                                </a>
                                <ul class="dropdown-menu">
                                    @can('gestionar-usuarios')
                                        <li><a class="dropdown-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">Usuarios</a></li>
                                    @endcan
                                    @can('gestionar-configuracion')
                                        <li><a class="dropdown-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" href="{{ route('configuracion.editar') }}">Configuración</a></li>
                                    @endcan
                                    @can('ver-auditoria')
                                        <li><a class="dropdown-item {{ request()->routeIs('auditoria.*') ? 'active' : '' }}" href="{{ route('auditoria.index') }}">Auditoría</a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        @if (! empty($cajaAbierta))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('caja.mi-caja') }}" title="Ver mi caja">
                                    <i class="bi bi-safe"></i> Caja abierta desde {{ $cajaAbierta->abierta_en->format('H:i') }}
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                <i class="bi bi-person-circle"></i>
                                {{ auth()->user()->nombre }} ({{ auth()->user()->rol->etiqueta() }})
                            </span>
                        </li>
                        @if (auth()->user()->rol !== \App\Enums\Rol::Cajero)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('password.*') ? 'active' : '' }}" href="{{ route('password.editar') }}">Cambiar contraseña</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link"><i class="bi bi-box-arrow-right"></i> Salir</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @yield('contenido')
    </main>

    <footer class="text-center text-secondary small py-3 border-top">
        NF Librería v{{ config('app.version', '1.0.0-dev') }}
        @auth
            · {{ auth()->user()->usuario }}
        @endauth
    </footer>

    <x-alertas />
    <x-confirmar />

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
