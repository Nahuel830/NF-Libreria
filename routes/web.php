<?php

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
});
