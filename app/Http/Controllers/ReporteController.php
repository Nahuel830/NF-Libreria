<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\EntradaStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        return view('reportes.index');
    }

    /**
     * @return array{string, string}
     */
    protected function rango(Request $request): array
    {
        $hasta = (string) $request->input('hasta', today()->toDateString());
        $desde = (string) $request->input('desde', today()->subDays(29)->toDateString());

        return [$desde, $hasta];
    }

    protected function csv(string $nombre, array $encabezados, iterable $filas): \Illuminate\Http\Response
    {
        $salida = fopen('php://temp', 'w+');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, $encabezados, ';');

            foreach ($filas as $fila) {
                fputcsv($salida, array_map(fn ($v) => $v === null ? '' : (string) $v, (array) $fila), ';');
            }

        rewind($salida);
        $contenido = stream_get_contents($salida);
        fclose($salida);

        return response($contenido, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombre}\"",
        ]);
    }

    protected function monedaCsv(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '');
    }

    protected function quiereCsv(Request $request): bool
    {
        return $request->input('formato') === 'csv';
    }

    public function resumen(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $filas = Venta::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->selectRaw("fecha::date as dia")
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'COMPLETADA') as cantidad")
            ->selectRaw("COALESCE(SUM(total) FILTER (WHERE estado = 'COMPLETADA'), 0) as total")
            ->selectRaw("COALESCE(SUM(descuento) FILTER (WHERE estado = 'COMPLETADA'), 0) as descuentos")
            ->selectRaw("COUNT(*) FILTER (WHERE estado = 'ANULADA') as anuladas")
            ->groupBy(DB::raw('fecha::date'))
            ->orderBy('dia')
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('resumen-ventas.csv',
                ['fecha', 'cantidad', 'total', 'descuentos', 'anuladas'],
                $filas->map(fn ($f) => [$f->dia, $f->cantidad, $this->monedaCsv($f->total), $this->monedaCsv($f->descuentos), $f->anuladas]));
        }

        return view('reportes.resumen', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function cajeros(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $filas = Venta::join('users', 'users.id', '=', 'ventas.user_id')
            ->where('ventas.estado', 'COMPLETADA')
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->select('users.nombre', 'users.usuario', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('users.id', 'users.nombre', 'users.usuario')
            ->orderByDesc('total')
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('ventas-por-cajero.csv',
                ['cajero', 'usuario', 'cantidad', 'total'],
                $filas->map(fn ($f) => [$f->nombre, $f->usuario, $f->cantidad, $this->monedaCsv($f->total)]));
        }

        return view('reportes.cajeros', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function metodos(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $filas = Venta::where('estado', 'COMPLETADA')
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->select('metodo_pago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('metodo_pago')
            ->orderByDesc('total')
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('ventas-por-metodo.csv',
                ['metodo', 'cantidad', 'total'],
                $filas->map(fn ($f) => [$f->metodo_pago, $f->cantidad, $this->monedaCsv($f->total)]));
        }

        return view('reportes.metodos', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function productos(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);
        $orden = $request->input('orden', 'cantidad');

        $filas = DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'v.id', '=', 'dv.venta_id')
            ->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->join('categorias as c', 'c.id', '=', 'p.categoria_id')
            ->where('v.estado', 'COMPLETADA')
            ->whereDate('v.fecha', '>=', $desde)->whereDate('v.fecha', '<=', $hasta)
            ->select('dv.codigo_producto as codigo', 'dv.nombre_producto as nombre', 'c.nombre as categoria',
                DB::raw('SUM(dv.cantidad) as cantidad'), DB::raw('SUM(dv.subtotal) as total'))
            ->groupBy('dv.codigo_producto', 'dv.nombre_producto', 'c.nombre')
            ->orderByDesc($orden === 'total' ? 'total' : 'cantidad')
            ->limit(50)
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('productos-mas-vendidos.csv',
                ['codigo', 'nombre', 'categoria', 'cantidad', 'total'],
                $filas->map(fn ($f) => [$f->codigo, $f->nombre, $f->categoria, $f->cantidad, $this->monedaCsv($f->total)]));
        }

        return view('reportes.productos', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta, 'orden' => $orden]);
    }

    public function categorias(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $filas = DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'v.id', '=', 'dv.venta_id')
            ->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->join('categorias as c', 'c.id', '=', 'p.categoria_id')
            ->where('v.estado', 'COMPLETADA')
            ->whereDate('v.fecha', '>=', $desde)->whereDate('v.fecha', '<=', $hasta)
            ->select('c.nombre as categoria', DB::raw('SUM(dv.cantidad) as cantidad'), DB::raw('SUM(dv.subtotal) as total'))
            ->groupBy('c.nombre')
            ->orderByDesc('total')
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('ventas-por-categoria.csv',
                ['categoria', 'cantidad', 'total'],
                $filas->map(fn ($f) => [$f->categoria, $f->cantidad, $this->monedaCsv($f->total)]));
        }

        return view('reportes.categorias', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function cierre(Request $request): View
    {
        $fecha = (string) $request->input('fecha', today()->toDateString());
        $cajeroId = $request->input('cajero_id');

        $base = Venta::where('estado', 'COMPLETADA')->whereDate('fecha', $fecha)
            ->when($cajeroId, fn ($c) => $c->where('user_id', $cajeroId));

        $porMetodo = (clone $base)->select('metodo_pago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('metodo_pago')->get();

        $anuladas = Venta::with('usuario')->where('estado', 'ANULADA')->whereDate('fecha', $fecha)
            ->when($cajeroId, fn ($c) => $c->where('user_id', $cajeroId))
            ->orderBy('id')->get();

        return view('reportes.cierre', [
            'fecha' => $fecha,
            'cajeroId' => $cajeroId,
            'cajeros' => User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
            'porMetodo' => $porMetodo,
            'cantidad' => (clone $base)->count(),
            'total' => (clone $base)->sum('total'),
            'efectivo' => (clone $base)->where('metodo_pago', 'EFECTIVO')->sum('total'),
            'anuladas' => $anuladas,
        ]);
    }

    public function inventario(Request $request): View|\Illuminate\Http\Response
    {
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->where('controla_stock', true)
            ->when($request->input('categoria_id'), fn ($c, $id) => $c->where('categoria_id', $id))
            ->orderBy('nombre')
            ->get();

        $filas = $productos->map(function ($p) {
            return [
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre,
                'stock' => $p->stock,
                'precio_compra' => $p->precio_compra,
                'valor_costo' => bcmul((string) $p->stock, $p->precio_compra, 2),
                'precio_venta' => $p->precio_venta,
                'valor_venta' => bcmul((string) $p->stock, $p->precio_venta, 2),
            ];
        });

        $totales = [
            'costo' => $filas->reduce(fn ($acc, $f) => bcadd($acc, $f['valor_costo'], 2), '0.00'),
            'venta' => $filas->reduce(fn ($acc, $f) => bcadd($acc, $f['valor_venta'], 2), '0.00'),
        ];

        if ($this->quiereCsv($request)) {
            return $this->csv('inventario-valorizado.csv',
                ['codigo', 'nombre', 'categoria', 'stock', 'precio_compra', 'valor_costo', 'precio_venta', 'valor_venta'],
                $filas->map(fn ($f) => [$f['codigo'], $f['nombre'], $f['categoria'], $f['stock'],
                    $this->monedaCsv($f['precio_compra']), $this->monedaCsv($f['valor_costo']),
                    $this->monedaCsv($f['precio_venta']), $this->monedaCsv($f['valor_venta'])]));
        }

        return view('reportes.inventario', [
            'filas' => $filas,
            'totales' => $totales,
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
            'categoriaId' => $request->input('categoria_id'),
        ]);
    }

    public function movimientos(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $consulta = MovimientoStock::with(['producto', 'usuario'])
            ->whereDate('created_at', '>=', $desde)->whereDate('created_at', '<=', $hasta)
            ->when($request->input('producto_id'), fn ($c, $id) => $c->where('producto_id', $id))
            ->when($request->input('tipo'), fn ($c, $t) => $c->where('tipo', $t))
            ->when($request->input('usuario_id'), fn ($c, $id) => $c->where('user_id', $id))
            ->orderByDesc('id');

        if ($this->quiereCsv($request)) {
            $filas = $consulta->limit(5000)->get();

            return $this->csv('movimientos-stock.csv',
                ['fecha', 'producto', 'tipo', 'cantidad', 'anterior', 'nuevo', 'usuario', 'motivo'],
                $filas->map(fn ($m) => [
                    $m->created_at->format('d/m/Y H:i'), $m->producto->codigo.' '.$m->producto->nombre,
                    $m->tipo, $m->cantidad, $m->stock_anterior, $m->stock_nuevo,
                    $m->usuario?->usuario ?? '', $m->motivo ?? '',
                ]));
        }

        return view('reportes.movimientos', [
            'movimientos' => $consulta->paginate(30)->withQueryString(),
            'desde' => $desde,
            'hasta' => $hasta,
            'productos' => Producto::orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'usuarios' => User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
            'tipos' => MovimientoStock::distinct()->orderBy('tipo')->pluck('tipo'),
        ]);
    }

    public function compras(Request $request): View|\Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rango($request);

        $filas = EntradaStock::leftJoin('proveedores as p', 'p.id', '=', 'entradas_stock.proveedor_id')
            ->where('entradas_stock.estado', 'REGISTRADA')
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->selectRaw("COALESCE(p.nombre, entradas_stock.proveedor, '—') as proveedor")
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw('SUM(total) as total')
            ->groupByRaw("COALESCE(p.nombre, entradas_stock.proveedor, '—')")
            ->orderByDesc('total')
            ->get();

        if ($this->quiereCsv($request)) {
            return $this->csv('compras-por-proveedor.csv',
                ['proveedor', 'cantidad', 'total'],
                $filas->map(fn ($f) => [$f->proveedor, $f->cantidad, $this->monedaCsv($f->total)]));
        }

        return view('reportes.compras', ['filas' => $filas, 'desde' => $desde, 'hasta' => $hasta]);
    }
}
