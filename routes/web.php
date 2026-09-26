<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\AjusteStockController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'mostrarFormulario'])->name('login');
    Route::post('/login', [LoginController::class, 'entrar'])->name('login.entrar');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'salir'])->name('logout');

    Route::get('/cambiar-password', [PasswordController::class, 'editar'])->name('password.editar');
    Route::put('/cambiar-password', [PasswordController::class, 'actualizar'])->name('password.actualizar');

    Route::get('/', function () {
        return view('inicio');
    })->name('inicio');

    Route::middleware('can:gestionar-usuarios')->prefix('usuarios')->name('usuarios.')->group(function (): void {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/crear', [UsuarioController::class, 'crear'])->name('crear');
        Route::post('/', [UsuarioController::class, 'guardar'])->name('guardar');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'editar'])->name('editar');
        Route::put('/{usuario}', [UsuarioController::class, 'actualizar'])->name('actualizar');
        Route::get('/{usuario}/password', [UsuarioController::class, 'password'])->name('password');
        Route::put('/{usuario}/password', [UsuarioController::class, 'actualizarPassword'])->name('password.actualizar');
        Route::patch('/{usuario}/estado', [UsuarioController::class, 'estado'])->name('estado');
    });

    Route::middleware('can:gestionar-configuracion')->group(function (): void {
        Route::get('/configuracion', [ConfiguracionController::class, 'editar'])->name('configuracion.editar');
        Route::put('/configuracion', [ConfiguracionController::class, 'actualizar'])->name('configuracion.actualizar');
    });

    Route::middleware('can:ver-auditoria')->prefix('auditoria')->name('auditoria.')->group(function (): void {
        Route::get('/', [AuditoriaController::class, 'index'])->name('index');
        Route::get('/{registro}', [AuditoriaController::class, 'ver'])->name('ver');
    });

    Route::middleware('can:gestionar-categorias')->prefix('categorias')->name('categorias.')->group(function (): void {
        Route::get('/', [CategoriaController::class, 'index'])->name('index');
        Route::get('/crear', [CategoriaController::class, 'crear'])->name('crear');
        Route::post('/', [CategoriaController::class, 'guardar'])->name('guardar');
        Route::get('/{categoria}/editar', [CategoriaController::class, 'editar'])->name('editar');
        Route::put('/{categoria}', [CategoriaController::class, 'actualizar'])->name('actualizar');
        Route::patch('/{categoria}/estado', [CategoriaController::class, 'estado'])->name('estado');
    });

    Route::middleware('can:ver-productos')->prefix('productos')->name('productos.')->group(function (): void {
        Route::get('/', [ProductoController::class, 'index'])->name('index');
        Route::get('/{producto}', [ProductoController::class, 'ver'])->name('ver')->whereNumber('producto');
    });

    Route::middleware('can:gestionar-productos')->prefix('productos')->name('productos.')->group(function (): void {
        Route::get('/crear', [ProductoController::class, 'crear'])->name('crear');
        Route::post('/', [ProductoController::class, 'guardar'])->name('guardar');
        Route::get('/sugerir-codigo', [ProductoController::class, 'sugerirCodigo'])->name('sugerir-codigo');
        Route::get('/{producto}/editar', [ProductoController::class, 'editar'])->name('editar');
        Route::put('/{producto}', [ProductoController::class, 'actualizar'])->name('actualizar');
        Route::patch('/{producto}/estado', [ProductoController::class, 'estado'])->name('estado');
    });

    Route::middleware('can:gestionar-stock')->group(function (): void {
        Route::get('/productos/{producto}/ajustar', [AjusteStockController::class, 'editar'])->name('productos.ajustar');
        Route::put('/productos/{producto}/ajustar', [AjusteStockController::class, 'actualizar'])->name('productos.ajustar.actualizar');
        Route::get('/inventario/stock-bajo', [ProductoController::class, 'stockBajo'])->name('inventario.stock-bajo');
    });

    Route::middleware('can:registrar-entradas')->prefix('entradas')->name('entradas.')->group(function (): void {
        Route::get('/', [EntradaController::class, 'index'])->name('index');
        Route::get('/crear', [EntradaController::class, 'crear'])->name('crear');
        Route::post('/', [EntradaController::class, 'guardar'])->name('guardar');
        Route::get('/{entrada}', [EntradaController::class, 'ver'])->name('ver');
        Route::post('/{entrada}/anular', [EntradaController::class, 'anular'])->name('anular');
    });

    Route::middleware('can:ver-productos')->prefix('api-interna')->name('api-interna.')->group(function (): void {
        Route::get('/productos/buscar', [EntradaController::class, 'buscar'])->name('productos.buscar');
    });
});
