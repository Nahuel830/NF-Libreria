<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularEntradaRequest;
use App\Http\Requests\EntradaRequest;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Services\EntradaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntradaController extends Controller
{
    public function index(Request $request): View
    {
        $entradas = EntradaStock::query()
            ->with('usuario')
            ->withCount('detalles')
            ->when($request->input('desde'), fn ($consulta, $desde) => $consulta->whereDate('fecha', '>=', $desde))
            ->when($request->input('hasta'), fn ($consulta, $hasta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->when($request->input('proveedor'), fn ($consulta, $p) => $consulta->where('proveedor', 'ilike', "%{$p}%"))
            ->when($request->input('estado'), fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('entradas.index', ['entradas' => $entradas]);
    }

    public function crear(): View
    {
        return view('entradas.crear');
    }

    public function guardar(EntradaRequest $request, EntradaService $servicio): RedirectResponse
    {
        try {
            $entrada = $servicio->registrar(
                $request->only(['proveedor', 'documento_referencia', 'observaciones']) + [
                    'actualizar_precio_compra' => $request->boolean('actualizar_precio_compra'),
                ],
                $request->input('items'),
                $request->user()
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('entradas.ver', $entrada)
            ->with('success', "Entrada {$entrada->numero()} registrada por Bs. {$entrada->total}.");
    }

    public function ver(EntradaStock $entrada): View
    {
        $entrada->load(['detalles.producto', 'usuario', 'anuladaPor']);

        return view('entradas.ver', ['entrada' => $entrada]);
    }

    public function anular(AnularEntradaRequest $request, EntradaStock $entrada, EntradaService $servicio): RedirectResponse
    {
        try {
            $servicio->anular($entrada, $request->input('motivo'), $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('entradas.ver', $entrada)
            ->with('success', "Entrada {$entrada->numero()} anulada y stock revertido.");
    }

    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $productos = Producto::query()
            ->where('activo', true)
            ->when($request->input('para') === 'entrada', fn ($consulta) => $consulta->where('controla_stock', true))
            ->where(function ($consulta) use ($q): void {
                $consulta->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('nombre', 'ilike', "%{$q}%");
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'codigo', 'nombre', 'stock', 'precio_compra', 'precio_venta', 'controla_stock']);

        return response()->json($productos);
    }
}
