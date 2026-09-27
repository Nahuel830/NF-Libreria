<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Services\ConfiguracionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function index(Request $request, ConfiguracionService $configuracion): View
    {
        $usuario = $request->user();

        if ($usuario->can('ver-reportes')) {
            return view('inicio', $this->panelAdmin($configuracion));
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
    protected function panelAdmin(ConfiguracionService $configuracion): array
    {
        $hoy = today()->toDateString();

        $ultimoBackupFecha = $configuracion->get('ultimo_backup_fecha');
        $ultimoBackupResultado = $configuracion->get('ultimo_backup_resultado');

        $alertaBackup = $ultimoBackupFecha === null
            || abs(now()->diffInHours($ultimoBackupFecha)) > 24
            || str_starts_with((string) $ultimoBackupResultado, 'error');

        $ventasHoy = Venta::where('estado', 'COMPLETADA')->whereDate('fecha', $hoy);
        $devHoy = number_format((float) \App\Models\Devolucion::whereDate('fecha', $hoy)->sum('total_devuelto'), 2, '.', '');
        $totalHoy = bcsub((string) (clone $ventasHoy)->sum('total'), $devHoy, 2);
        $cantidadHoy = (clone $ventasHoy)->count();

        $devMetodo = \App\Models\Devolucion::whereDate('fecha', $hoy)
            ->selectRaw('metodo_reembolso')
            ->selectRaw('SUM(total_devuelto) as devuelto')
            ->groupBy('metodo_reembolso')
            ->pluck('devuelto', 'metodo_reembolso');

        $porMetodo = Venta::where('estado', 'COMPLETADA')->whereDate('fecha', $hoy)
            ->select('metodo_pago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('metodo_pago')->get()
            ->map(function ($fila) use ($devMetodo) {
                $fila->total = bcsub($fila->total, number_format((float) ($devMetodo[$fila->metodo_pago] ?? 0), 2, '.', ''), 2);

                return $fila;
            });

        $devDias = \App\Models\Devolucion::whereDate('fecha', '>=', today()->subDays(6)->toDateString())
            ->select(DB::raw('fecha::date as dia'), DB::raw('SUM(total_devuelto) as devuelto'))
            ->groupBy(DB::raw('fecha::date'))
            ->pluck('devuelto', 'dia');

        $ultimos7 = Venta::where('estado', 'COMPLETADA')
            ->whereDate('fecha', '>=', today()->subDays(6)->toDateString())
            ->select(DB::raw('fecha::date as dia'), DB::raw('SUM(total) as total'))
            ->groupBy(DB::raw('fecha::date'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $etiquetas = [];
        $valores = [];

        for ($i = 6; $i >= 0; $i--) {
            $dia = today()->subDays($i)->toDateString();
            $etiquetas[] = $dia;
            $bruto = (float) ($ultimos7->get($dia)->total ?? 0);
            $valores[] = $bruto - (float) ($devDias[$dia] ?? 0);
        }

        return [
            'esAdmin' => true,
            'totalHoy' => $totalHoy,
            'cantidadHoy' => $cantidadHoy,
            'ticketPromedio' => $cantidadHoy > 0 ? $totalHoy / $cantidadHoy : 0,
            'anuladasHoy' => Venta::where('estado', 'ANULADA')->whereDate('fecha', $hoy)->count(),
            'alertaBackup' => $alertaBackup,
            'ultimoBackupFecha' => $ultimoBackupFecha,
            'ultimoBackupResultado' => $ultimoBackupResultado,
            'nuevosDispositivos' => \App\Models\Auditoria::where('accion', 'LOGIN_NUEVO_DISPOSITIVO')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
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
