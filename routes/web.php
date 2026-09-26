<?php

use App\Http\Controllers\Admin\AuditoriaController;
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
});
