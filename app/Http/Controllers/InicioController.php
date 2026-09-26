<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        if ($usuario->can('ver-reportes')) {
            return view('inicio', $this->panelAdmin());
        }

        $hoy = today()->toDateString();

        $ventasHoy = Venta::where('user_id', $usuario->id)
            ->where('estado', 'COMPLETADA')
            ->whereDate('fecha', $hoy);

        return view('inicio', [
            'esAdmin' => false,
            'misVentasHoy' => (clone $ventasHoy)->count(),
            'miTotalHoy' => (clone $ventasHoy)->sum('total'),
            'misUltimas' => Venta::where('user_id', $usuario->id)
                ->orderByDesc('id')->limit(5)->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function panelAdmin(): array
    {
        $hoy = today()->toDateString();

        $ventasHoy = Venta::where('estado', 'COMPLETADA')->whereDate('fecha', $hoy);
        $totalHoy = (clone $ventasHoy)->sum('total');
        $cantidadHoy = (clone $ventasHoy)->count();

        $porMetodo = Venta::where('estado', 'COMPLETADA')->whereDate('fecha', $hoy)
            ->select('metodo_pago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('metodo_pago')->get();

        $ultimos7 = Venta::where('estado', 'COMPLETADA')
            ->whereDate('fecha', '>=', today()->subDays(6)->toDateString())
            ->select(DB::raw('fecha::date as dia'), DB::raw('SUM(total) as total'))
            ->groupBy(DB::raw('fecha::date'))
            ->orderBy('dia')
            ->get();

        $etiquetas = [];
        $valores = [];

        for ($i = 6; $i >= 0; $i--) {
            $dia = today()->subDays($i)->toDateString();
            $etiquetas[] = $dia;
            $valores[] = (float) ($ultimos7->firstWhere('dia', $dia)->total ?? 0);
        }

        return [
            'esAdmin' => true,
            'totalHoy' => $totalHoy,
            'cantidadHoy' => $cantidadHoy,
            'ticketPromedio' => $cantidadHoy > 0 ? $totalHoy / $cantidadHoy : 0,
            'anuladasHoy' => Venta::where('estado', 'ANULADA')->whereDate('fecha', $hoy)->count(),
            'porMetodo' => $porMetodo,
            'graficoEtiquetas' => $etiquetas,
            'graficoValores' => $valores,
            'stockBajo' => Producto::with('categoria')->where('activo', true)
                ->where('controla_stock', true)->whereColumn('stock', '<=', 'stock_minimo')
                ->orderByRaw('(stock - stock_minimo) ASC')->limit(10)->get(),
            'ultimasVentas' => Venta::with('usuario')->orderByDesc('id')->limit(10)->get(),
        ];
    }
}
