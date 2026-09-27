@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
    <x-page-header titulo="Reportes" />

    <ul>
        <li><a href="{{ route('reportes.resumen') }}">Resumen de ventas por día</a></li>
        <li><a href="{{ route('reportes.cajeros') }}">Ventas por cajero</a></li>
        <li><a href="{{ route('reportes.metodos') }}">Ventas por método de pago</a></li>
        <li><a href="{{ route('reportes.productos') }}">Productos más vendidos</a></li>
        <li><a href="{{ route('reportes.categorias') }}">Ventas por categoría</a></li>
        <li><a href="{{ route('reportes.cierre') }}">Cierre del día</a></li>
        <li><a href="{{ route('reportes.inventario') }}">Inventario valorizado</a></li>
        <li><a href="{{ route('reportes.movimientos') }}">Movimientos de stock</a></li>
        <li><a href="{{ route('reportes.compras') }}">Compras por proveedor</a></li>
    </ul>
@endsection
