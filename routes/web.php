<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\AjusteStockController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VentaController;
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

    Route::get('/', [InicioController::class, 'index'])->name('inicio');

    Route::middleware('can:ver-reportes')->prefix('reportes')->name('reportes.')->group(function (): void {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/resumen', [ReporteController::class, 'resumen'])->name('resumen');
        Route::get('/cajeros', [ReporteController::class, 'cajeros'])->name('cajeros');
        Route::get('/metodos', [ReporteController::class, 'metodos'])->name('metodos');
        Route::get('/productos', [ReporteController::class, 'productos'])->name('productos');
        Route::get('/categorias', [ReporteController::class, 'categorias'])->name('categorias');
        Route::get('/cierre', [ReporteController::class, 'cierre'])->name('cierre');
        Route::get('/inventario', [ReporteController::class, 'inventario'])->name('inventario');
        Route::get('/movimientos', [ReporteController::class, 'movimientos'])->name('movimientos');
        Route::get('/compras', [ReporteController::class, 'compras'])->name('compras');
    });

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

    Route::middleware('can:gestionar-usuarios')->get('/estilos', function () {
        abort_unless(app()->environment('local'), 404);

        return view('estilos');
    })->name('estilos');

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
        Route::get('/etiquetas', [ProductoController::class, 'etiquetas'])->name('etiquetas');
        Route::get('/importar', [ImportacionController::class, 'importar'])->name('importar');
        Route::get('/importar/plantilla', [ImportacionController::class, 'plantilla'])->name('importar.plantilla');
        Route::post('/importar/vista-previa', [ImportacionController::class, 'vistaPrevia'])->name('importar.vista-previa');
        Route::post('/importar/confirmar', [ImportacionController::class, 'confirmar'])->name('importar.confirmar');
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

    Route::middleware('can:gestionar-proveedores')->prefix('proveedores')->name('proveedores.')->group(function (): void {
        Route::get('/', [ProveedorController::class, 'index'])->name('index');
        Route::get('/crear', [ProveedorController::class, 'crear'])->name('crear');
        Route::post('/', [ProveedorController::class, 'guardar'])->name('guardar');
        Route::get('/buscar', [ProveedorController::class, 'buscar'])->name('buscar');
        Route::post('/rapido', [ProveedorController::class, 'guardarRapido'])->name('rapido');
        Route::get('/{proveedor}', [ProveedorController::class, 'ver'])->name('ver');
        Route::get('/{proveedor}/editar', [ProveedorController::class, 'editar'])->name('editar');
        Route::put('/{proveedor}', [ProveedorController::class, 'actualizar'])->name('actualizar');
        Route::patch('/{proveedor}/estado', [ProveedorController::class, 'estado'])->name('estado');
    });

    Route::middleware('can:ver-productos')->prefix('api-interna')->name('api-interna.')->group(function (): void {
        Route::get('/productos/buscar', [EntradaController::class, 'buscar'])->name('productos.buscar');
    });

    Route::middleware('can:realizar-ventas')->prefix('ventas')->name('ventas.')->group(function (): void {
        Route::get('/nueva', [VentaController::class, 'nueva'])->name('nueva');
        Route::post('/', [VentaController::class, 'cobrar'])->name('cobrar');
        Route::get('/', [VentaController::class, 'index'])->name('index');
        Route::get('/{venta}', [VentaController::class, 'ver'])->name('ver')->whereNumber('venta');
        Route::get('/{venta}/ticket', [VentaController::class, 'ticket'])->name('ticket')->whereNumber('venta');
        Route::post('/{venta}/anular', [VentaController::class, 'anular'])->name('anular')->middleware('can:anular-ventas');
    });
});
