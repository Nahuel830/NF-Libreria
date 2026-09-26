@extends('layouts.app')

@section('titulo', 'Sesión expirada')

@section('contenido')
    <div class="text-center mt-5">
        <h1>419 — Sesión expirada</h1>
        <p>La sesión expiró, vuelve a intentarlo.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Ir al inicio de sesión</a>
    </div>
@endsection
