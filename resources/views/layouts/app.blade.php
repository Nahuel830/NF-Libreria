<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'NF Librería')</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ auth()->check() ? route('inicio') : route('login') }}">
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
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Ventas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('ventas.nueva') }}">Nueva venta</a></li>
                                    <li><a class="dropdown-item" href="{{ route('ventas.index') }}">Historial de ventas</a></li>
                                </ul>
                            </li>
                        @endcan

                        @canany(['gestionar-categorias', 'ver-productos', 'registrar-entradas'])
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Inventario
                                </a>
                                <ul class="dropdown-menu">
                                    @can('gestionar-categorias')
                                        <li><a class="dropdown-item" href="{{ route('categorias.index') }}">Categorías</a></li>
                                    @endcan
                                    @can('ver-productos')
                                        <li><a class="dropdown-item" href="{{ route('productos.index') }}">Productos</a></li>
                                    @endcan
                                    @can('registrar-entradas')
                                        <li><a class="dropdown-item" href="{{ route('entradas.index') }}">Entradas de mercadería</a></li>
                                    @endcan
                                    @can('gestionar-stock')
                                        <li><a class="dropdown-item" href="{{ route('inventario.stock-bajo') }}">Stock bajo</a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany

                        @can('ver-reportes')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reportes.index') }}">Reportes</a>
                            </li>
                        @endcan

                        @canany(['gestionar-usuarios', 'gestionar-configuracion', 'ver-auditoria'])
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Administración
                                </a>
                                <ul class="dropdown-menu">
                                    @can('gestionar-usuarios')
                                        <li><a class="dropdown-item" href="{{ route('usuarios.index') }}">Usuarios</a></li>
                                    @endcan
                                    @can('gestionar-configuracion')
                                        <li><a class="dropdown-item" href="{{ route('configuracion.editar') }}">Configuración</a></li>
                                    @endcan
                                    @can('ver-auditoria')
                                        <li><a class="dropdown-item" href="{{ route('auditoria.index') }}">Auditoría</a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                {{ auth()->user()->nombre }} ({{ auth()->user()->rol->etiqueta() }})
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('password.editar') }}">Cambiar contraseña</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link">Salir</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </nav>

    <main class="container py-4">
        @foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $clave => $tipo)
            @if (session($clave))
                <div class="alert alert-{{ $tipo }} alert-dismissible fade show" role="alert">
                    {{ session($clave) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @yield('contenido')
    </main>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
