<?php

namespace App\Providers;

use App\Enums\Rol;
use App\Models\User;
use App\Services\ConfiguracionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // WEB-1 1.4: regla central de contraseñas para todos los roles.
        Password::defaults(function () {
            $regla = Password::min(10)->letters()->numbers();

            if (config('seguridad.password_verificar_filtradas')) {
                $regla->uncompromised();
            }

            return $regla;
        });

        $soloAdmin = fn (User $user) => $user->rol === Rol::Admin;
        $adminOEncargado = fn (User $user) => in_array($user->rol, [Rol::Admin, Rol::Encargado], true);
        $todos = fn (User $user) => in_array($user->rol, [Rol::Admin, Rol::Encargado, Rol::Cajero], true);

        Gate::define('gestionar-usuarios', $soloAdmin);
        Gate::define('gestionar-configuracion', $soloAdmin);
        Gate::define('ver-auditoria', $soloAdmin);
        Gate::define('gestionar-proveedores', $adminOEncargado);
        Gate::define('gestionar-clientes', $adminOEncargado);
        Gate::define('gestionar-categorias', $adminOEncargado);
        Gate::define('gestionar-productos', $adminOEncargado);
        Gate::define('ver-productos', $todos);
        Gate::define('gestionar-stock', $adminOEncargado);
        Gate::define('registrar-entradas', $adminOEncargado);
        Gate::define('realizar-ventas', $todos);
        Gate::define('ver-todas-las-ventas', $adminOEncargado);
        Gate::define('anular-ventas', $adminOEncargado);
        Gate::define('aplicar-descuentos', $adminOEncargado);
        Gate::define('ver-reportes', $adminOEncargado);

        View::composer('layouts.app', function ($view): void {
            $view->with(
                'nombreNegocio',
                app(ConfiguracionService::class)->get('nombre_negocio', 'NF Librería')
            );

            $usuario = auth()->user();
            $view->with(
                'cajaAbierta',
                $usuario ? \App\Models\Caja::abiertaDe($usuario) : null
            );
        });
    }
}
