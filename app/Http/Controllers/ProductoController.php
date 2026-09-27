<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\AuditoriaService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $productos = Producto::query()
            ->with('categoria')
            ->when($request->input('q'), function ($consulta, $q): void {
                $consulta->where(function ($sub) use ($q): void {
                    $sub->where('codigo', 'ilike', "%{$q}%")
                        ->orWhere('nombre', 'ilike', "%{$q}%");
                });
            })
            ->when($request->input('categoria_id'), fn ($consulta, $id) => $consulta->where('categoria_id', $id))
            ->when($request->input('estado') !== null && $request->input('estado') !== '', fn ($consulta) => $consulta->where('activo', $request->boolean('estado')))
            ->when($request->boolean('stock_bajo'), function ($consulta): void {
                $consulta->where('controla_stock', true)->whereColumn('stock', '<=', 'stock_minimo');
            })
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function crear(): View
    {
        return view('productos.crear', [
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => $this->unidades(),
        ]);
    }

    public function guardar(ProductoRequest $request, AuditoriaService $auditoria, StockService $stock): RedirectResponse
    {
        if ($this->requiereConfirmacionPrecio($request)) {
            return back()->withInput()->with('warning', 'Advertencia: el precio de venta es menor al precio de compra. Revisa los valores y pulsa Guardar de nuevo para confirmar.');
        }

        $producto = DB::transaction(function () use ($request, $auditoria, $stock) {
            $producto = Producto::create($this->datos($request));

            $inicial = (int) $request->input('stock_inicial', 0);

            if ($inicial > 0) {
                $stock->mover($producto->id, $inicial, 'INICIAL', 'Stock inicial');
                $producto->refresh();
            }

            $auditoria->registrar('CREAR', "Se creó el producto '{$producto->codigo} - {$producto->nombre}'.", $producto, null, $producto->toArray());

            return $producto;
        });

        return redirect()->route('productos.index')->with('success', "Producto '{$producto->codigo}' creado.");
    }

    public function ver(Producto $producto): View
    {
        $producto->load('categoria');

        $movimientos = $producto->movimientos()->with('usuario')->orderByDesc('id')->paginate(20);

        return view('productos.ver', ['producto' => $producto, 'movimientos' => $movimientos]);
    }

    public function editar(Producto $producto): View
    {
        return view('productos.editar', [
            'producto' => $producto,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => $this->unidades(),
        ]);
    }

    public function actualizar(ProductoRequest $request, Producto $producto, AuditoriaService $auditoria): RedirectResponse
    {
        if ($this->requiereConfirmacionPrecio($request)) {
            return back()->withInput()->with('warning', 'Advertencia: el precio de venta es menor al precio de compra. Revisa los valores y pulsa Guardar de nuevo para confirmar.');
        }

        $antes = $producto->toArray();
        $precioCompraAntes = $producto->precio_compra;
        $precioVentaAntes = $producto->precio_venta;

        $producto->fill($this->datos($request))->save();
        $producto->refresh();

        $auditoria->registrar('EDITAR', "Se editó el producto '{$producto->codigo} - {$producto->nombre}'.", $producto, $antes, $producto->toArray());

        if ($precioCompraAntes != $producto->precio_compra || $precioVentaAntes != $producto->precio_venta) {
            $auditoria->registrar(
                'CAMBIO_PRECIO',
                "Cambio de precio en '{$producto->codigo}': compra {$precioCompraAntes} → {$producto->precio_compra}, venta {$precioVentaAntes} → {$producto->precio_venta}.",
                $producto,
                ['precio_compra' => $precioCompraAntes, 'precio_venta' => $precioVentaAntes],
                ['precio_compra' => $producto->precio_compra, 'precio_venta' => $producto->precio_venta]
            );
        }

        return redirect()->route('productos.index')->with('success', "Producto '{$producto->codigo}' actualizado.");
    }

    public function estado(Request $request, Producto $producto, AuditoriaService $auditoria): RedirectResponse
    {
        $producto->forceFill(['activo' => ! $producto->activo])->save();

        $accion = $producto->activo ? 'ACTIVAR' : 'DESACTIVAR';
        $auditoria->registrar(
            $accion,
            "Se ".($producto->activo ? 'activó' : 'desactivó')." el producto '{$producto->codigo}'.",
            $producto,
            ['activo' => ! $producto->activo],
            ['activo' => $producto->activo]
        );

        return redirect()->route('productos.index')
            ->with('success', "Producto '{$producto->codigo}' ".($producto->activo ? 'activado' : 'desactivado').'.');
    }

    public function etiquetas(Request $request): View
    {
        $filas = max(1, min(20, (int) $request->input('filas', 8)));
        $columnas = max(1, min(5, (int) $request->input('columnas', 3)));

        $productos = Producto::query()
            ->where('activo', true)
            ->when($request->input('categoria_id'), fn ($consulta, $id) => $consulta->where('categoria_id', $id))
            ->when($request->boolean('solo_sin_barras'), fn ($consulta) => $consulta->whereNull('codigo_barras'))
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        $generador = new BarcodeGeneratorSVG();
        $etiquetas = $productos->map(fn (Producto $p) => [
            'nombre' => $p->nombre,
            'codigo' => $p->codigo_barras ?? $p->codigo,
            // SVG generado en el servidor (solo gráficos, sin HTML de usuarios).
            'svg' => $generador->getBarcode($p->codigo_barras ?? $p->codigo, BarcodeGeneratorSVG::TYPE_CODE_128),
        ]);

        return view('productos.etiquetas', [
            'etiquetas' => $etiquetas,
            'filas' => $filas,
            'columnas' => $columnas,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function stockBajo(): View
    {
        $productos = Producto::query()
            ->with('categoria')
            ->where('activo', true)
            ->where('controla_stock', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderByRaw('(stock - stock_minimo) ASC')
            ->paginate(25);

        return view('inventario.stock-bajo', ['productos' => $productos]);
    }

    public function sugerirCodigo(Request $request): JsonResponse
    {
        $categoria = Categoria::find($request->input('categoria_id'));

        if (! $categoria) {
            return response()->json(['codigo' => ''], 422);
        }

        $prefijo = mb_strtoupper(mb_substr(Str::ascii($categoria->nombre), 0, 3));

        $maximo = Producto::where('codigo', 'like', $prefijo.'-%')
            ->pluck('codigo')
            ->map(fn (string $codigo) => (int) ltrim(mb_substr($codigo, 4), '0'))
            ->max() ?? 0;

        return response()->json(['codigo' => $prefijo.'-'.str_pad((string) ($maximo + 1), 3, '0', STR_PAD_LEFT)]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function datos(ProductoRequest $request): array
    {
        return [
            'codigo' => $request->input('codigo'),
            'codigo_barras' => $request->input('codigo_barras'),
            'nombre' => trim((string) $request->input('nombre')),
            'descripcion' => $request->input('descripcion'),
            'categoria_id' => $request->input('categoria_id'),
            'marca' => $request->input('marca'),
            'unidad' => $request->input('unidad'),
            'precio_compra' => $request->precioCompra(),
            'precio_venta' => $request->precioVenta(),
            'stock_minimo' => (int) ($request->input('stock_minimo') ?? 0),
            'controla_stock' => $request->boolean('controla_stock'),
        ];
    }

    protected function requiereConfirmacionPrecio(ProductoRequest $request): bool
    {
        return ! $request->boolean('confirmar_precio')
            && (float) $request->precioVenta() < (float) $request->precioCompra();
    }

    /**
     * @return string[]
     */
    protected function unidades(): array
    {
        return ['unidad', 'paquete', 'caja', 'resma', 'hoja', 'docena'];
    }
}
