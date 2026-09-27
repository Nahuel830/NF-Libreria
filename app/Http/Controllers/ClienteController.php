<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $clientes = Cliente::query()
            ->withCount('ventas')
            ->when($request->input('q'), function ($consulta, $q): void {
                $consulta->where(function ($sub) use ($q): void {
                    $sub->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('ci_nit', 'ilike', "%{$q}%");
                });
            })
            ->when($request->input('estado') !== null && $request->input('estado') !== '', fn ($consulta) => $consulta->where('activo', $request->boolean('estado')))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('clientes.index', ['clientes' => $clientes]);
    }

    public function crear(): View
    {
        return view('clientes.crear');
    }

    public function guardar(ClienteRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        $auditoria->registrar('CREAR', "Se creó el cliente '{$cliente->nombre}'.", $cliente, null, $cliente->toArray());

        return redirect()->route('clientes.index')->with('success', "Cliente '{$cliente->nombre}' creado.");
    }

    public function ver(Cliente $cliente): View
    {
        $ventas = $cliente->ventas()->orderByDesc('id')->paginate(20);
        $total = $cliente->ventas()->where('estado', 'COMPLETADA')->sum('total');

        return view('clientes.ver', [
            'cliente' => $cliente,
            'ventas' => $ventas,
            'total' => $total,
            'ultima' => $cliente->ventas()->orderByDesc('fecha')->first(),
        ]);
    }

    public function editar(Cliente $cliente): View
    {
        return view('clientes.editar', ['cliente' => $cliente]);
    }

    public function actualizar(ClienteRequest $request, Cliente $cliente, AuditoriaService $auditoria): RedirectResponse
    {
        $antes = $cliente->toArray();
        $cliente->fill($request->validated())->save();

        $auditoria->registrar('EDITAR', "Se editó el cliente '{$cliente->nombre}'.", $cliente, $antes, $cliente->fresh()->toArray());

        return redirect()->route('clientes.index')->with('success', "Cliente '{$cliente->nombre}' actualizado.");
    }

    public function estado(Request $request, Cliente $cliente, AuditoriaService $auditoria): RedirectResponse
    {
        $cliente->forceFill(['activo' => ! $cliente->activo])->save();

        $accion = $cliente->activo ? 'ACTIVAR' : 'DESACTIVAR';
        $auditoria->registrar($accion, "Se ".($cliente->activo ? 'activó' : 'desactivó')." el cliente '{$cliente->nombre}'.", $cliente);

        return redirect()->route('clientes.index')
            ->with('success', "Cliente '{$cliente->nombre}' ".($cliente->activo ? 'activado' : 'desactivado').'.');
    }

    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        return response()->json(
            Cliente::where('activo', true)
                ->where(function ($consulta) use ($q): void {
                    $consulta->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('ci_nit', 'ilike', "%{$q}%");
                })
                ->orderBy('nombre')
                ->limit(10)
                ->get(['id', 'nombre', 'ci_nit'])
        );
    }

    public function guardarRapido(Request $request, AuditoriaService $auditoria): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ci_nit' => ['nullable', 'string', 'max:20', 'unique:clientes,ci_nit'],
        ]);

        $cliente = Cliente::create([
            'nombre' => trim($datos['nombre']),
            'ci_nit' => isset($datos['ci_nit']) && trim((string) $datos['ci_nit']) !== '' ? trim((string) $datos['ci_nit']) : null,
        ]);

        $auditoria->registrar('CREAR', "Se creó el cliente '{$cliente->nombre}' (rápido).", $cliente, null, $cliente->toArray());

        return response()->json(['id' => $cliente->id, 'nombre' => $cliente->nombre]);
    }
}
