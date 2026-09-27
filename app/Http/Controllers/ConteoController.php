<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConteoController extends Controller
{
    public function index(Request $request): View
    {
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        $categoriaId = $request->input('categoria_id', $categorias->first()?->id);

        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->where('controla_stock', true)
            ->when($categoriaId, fn ($c, $id) => $c->where('categoria_id', $id))
            ->orderBy('nombre')
            ->paginate(50)
            ->withQueryString();

        return view('inventario.conteo', [
            'categorias' => $categorias,
            'categoriaId' => $categoriaId,
            'productos' => $productos,
        ]);
    }

    public function guardar(Request $request, StockService $stock): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
            'conteos' => ['required', 'array', 'min:1'],
            'conteos.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $motivo = trim((string) ($datos['motivo'] ?? ''));

        if ($motivo === '') {
            $motivo = 'Conteo físico inicial';
        }

        $conteos = collect($datos['conteos'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v]);

        if ($conteos->isEmpty()) {
            return back()->withErrors(['conteos' => 'Ingresa al menos una cantidad contada.']);
        }

        $ajustados = DB::transaction(function () use ($conteos, $motivo, $stock) {
            $n = 0;

            foreach ($conteos as $id => $real) {
                $producto = Producto::whereKey($id)->where('activo', true)->where('controla_stock', true)->first();

                if (! $producto) {
                    continue;
                }

                if ($stock->ajustar($producto, $real, $motivo) !== null) {
                    $n++;
                }
            }

            return $n;
        });

        return back()->with('success', "Conteo guardado: {$ajustados} productos ajustados.");
    }

    public function hoja(Request $request): \Illuminate\Http\Response
    {
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->where('controla_stock', true)
            ->when($request->input('categoria_id'), fn ($c, $id) => $c->where('categoria_id', $id))
            ->orderBy('categoria_id')
            ->orderBy('nombre')
            ->get();

        $salida = fopen('php://temp', 'w+');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['codigo', 'nombre', 'stock_sistema', 'cantidad_contada'], ';');

        foreach ($productos as $producto) {
            fputcsv($salida, [$producto->codigo, $producto->nombre, $producto->stock, ''], ';');
        }

        rewind($salida);
        $contenido = stream_get_contents($salida);
        fclose($salida);

        return response($contenido, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="hoja-conteo.csv"',
        ]);
    }
}
