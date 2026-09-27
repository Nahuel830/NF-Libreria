<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $registros = Auditoria::query()
            ->with('usuario')
            ->when($request->input('desde'), fn ($consulta, $desde) => $consulta->whereDate('created_at', '>=', $desde))
            ->when($request->input('hasta'), fn ($consulta, $hasta) => $consulta->whereDate('created_at', '<=', $hasta))
            ->when($request->input('usuario_id'), fn ($consulta, $id) => $consulta->where('user_id', $id))
            ->when($request->input('accion'), fn ($consulta, $accion) => $consulta->where('accion', $accion))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'usuarios' => User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
            'acciones' => Auditoria::distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }

    public function ver(Auditoria $registro): View
    {
        $registro->load('usuario');

        return view('auditoria.ver', ['registro' => $registro]);
    }

    public function actividad(Request $request): View
    {
        $registros = Auditoria::query()
            ->with('usuario')
            ->whereIn('accion', ['LOGIN', 'LOGIN_FALLIDO'])
            ->when($request->input('usuario_id'), fn ($consulta, $id) => $consulta->where('user_id', $id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('auditoria.actividad', [
            'registros' => $registros,
            'usuarios' => User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
        ]);
    }
}
