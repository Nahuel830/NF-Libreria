<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorRequest;
use App\Models\Proveedor;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $proveedores = Proveedor::query()
            ->withCount('entradas')
            ->when($request->input('q'), fn ($consulta, $q) => $consulta->where('nombre', 'ilike', "%{$q}%"))
            ->when($request->input('estado') !== null && $request->input('estado') !== '', fn ($consulta) => $consulta->where('activo', $request->boolean('estado')))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('proveedores.index', ['proveedores' => $proveedores]);
    }

    public function crear(): View
    {
        return view('proveedores.crear');
    }

    public function guardar(ProveedorRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $proveedor = Proveedor::create($request->validated());

        $auditoria->registrar('CREAR', "Se creó el proveedor '{$proveedor->nombre}'.", $proveedor, null, $proveedor->toArray());

        return redirect()->route('proveedores.index')->with('success', "Proveedor '{$proveedor->nombre}' creado.");
    }

    public function ver(Proveedor $proveedor, Request $request): View
    {
        $proveedor->loadCount('entradas');

        $base = $proveedor->entradas()
            ->when($request->input('desde'), fn ($c, $d) => $c->whereDate('fecha', '>=', $d))
            ->when($request->input('hasta'), fn ($c, $h) => $c->whereDate('fecha', '<=', $h));

        $total = (clone $base)->where('estado', 'REGISTRADA')->sum('total');

        $entradas = $base->withCount('detalles')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $productos = \App\Models\DetalleEntrada::query()
            ->join('entradas_stock as e', 'e.id', '=', 'detalle_entradas.entrada_id')
            ->join('productos as p', 'p.id', '=', 'detalle_entradas.producto_id')
            ->where('e.proveedor_id', $proveedor->id)
            ->where('e.estado', 'REGISTRADA')
            ->select('p.codigo', 'p.nombre', \Illuminate\Support\Facades\DB::raw('MAX(detalle_entradas.costo_unitario) as ultimo_costo'), \Illuminate\Support\Facades\DB::raw('SUM(detalle_entradas.cantidad) as total_cantidad'))
            ->groupBy('p.codigo', 'p.nombre')
            ->orderByDesc('total_cantidad')
            ->limit(20)
            ->get();

        return view('proveedores.ver', [
            'proveedor' => $proveedor,
            'entradas' => $entradas,
            'total' => $total,
            'productos' => $productos,
        ]);
    }

    public function editar(Proveedor $proveedor): View
    {
        return view('proveedores.editar', ['proveedor' => $proveedor]);
    }

    public function actualizar(ProveedorRequest $request, Proveedor $proveedor, AuditoriaService $auditoria): RedirectResponse
    {
        $antes = $proveedor->toArray();
        $proveedor->fill($request->validated())->save();

        $auditoria->registrar('EDITAR', "Se editó el proveedor '{$proveedor->nombre}'.", $proveedor, $antes, $proveedor->fresh()->toArray());

        return redirect()->route('proveedores.index')->with('success', "Proveedor '{$proveedor->nombre}' actualizado.");
    }

    public function estado(Request $request, Proveedor $proveedor, AuditoriaService $auditoria): RedirectResponse
    {
        $proveedor->forceFill(['activo' => ! $proveedor->activo])->save();

        $accion = $proveedor->activo ? 'ACTIVAR' : 'DESACTIVAR';
        $auditoria->registrar($accion, "Se ".($proveedor->activo ? 'activó' : 'desactivó')." el proveedor '{$proveedor->nombre}'.", $proveedor);

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor '{$proveedor->nombre}' ".($proveedor->activo ? 'activado' : 'desactivado').'.');
    }

    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        return response()->json(
            Proveedor::where('activo', true)
                ->where('nombre', 'ilike', "%{$q}%")
                ->orderBy('nombre')
                ->limit(10)
                ->get(['id', 'nombre'])
        );
    }

    public function guardarRapido(Request $request, AuditoriaService $auditoria): JsonResponse
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:150', 'unique:proveedores,nombre']]);

        $proveedor = Proveedor::create(['nombre' => trim($datos['nombre'])]);

        $auditoria->registrar('CREAR', "Se creó el proveedor '{$proveedor->nombre}' (rápido).", $proveedor, null, $proveedor->toArray());

        return response()->json(['id' => $proveedor->id, 'nombre' => $proveedor->nombre]);
    }
}
