<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Http\Requests\AnularVentaRequest;
use App\Http\Requests\VentaRequest;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\ConfiguracionService;
use App\Services\VentaService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function nueva(CajaService $cajas)
    {
        if ($cajas->exigirAbierta() && ! \App\Models\Caja::abiertaDe(request()->user())) {
            return redirect()->route('caja.abrir')->with('warning', 'Debes abrir tu caja antes de vender.');
        }

        return view('ventas.nueva', [
            'token' => (string) Str::uuid(),
            'metodos' => MetodoPago::cases(),
        ]);
    }

    public function cobrar(VentaRequest $request, VentaService $servicio)
    {
        try {
            $venta = $servicio->registrar(
                $request->input('items'),
                $request->only(['token', 'metodo_pago', 'descuento', 'monto_recibido', 'cliente_id', 'cliente_nombre', 'observaciones']),
                $request->user()
            );
        } catch (DomainException $e) {
            return $this->errorVenta($request, $e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->errorVenta($request, $e->getMessage() ?: 'No tienes permiso para esta acción.');
        }

        if ($request->expectsJson()) {
            $url = route('ventas.ticket', $venta);

            if ($venta->cambio !== null) {
                $url .= '?cambio='.$venta->cambio;
            }

            return response()->json(['url' => $url, 'cambio' => $venta->cambio]);
        }

        return redirect()->route('ventas.ticket', $venta)
            ->with('success', "Venta {$venta->numero()} registrada por Bs. {$venta->total}.")
            ->with('cambio', $venta->cambio);
    }

    protected function errorVenta(VentaRequest $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje], 422);
        }

        return back()->withInput()->withErrors(['venta' => $mensaje]);
    }

    public function ticket(Venta $venta, ConfiguracionService $configuracion): View
    {
        $this->autorizarVerVenta($venta);

        $venta->load(['detalles', 'usuario']);

        return view('ventas.ticket', [
            'venta' => $venta,
            'nombreNegocio' => $configuracion->get('nombre_negocio', 'NF Librería'),
            'direccion' => $configuracion->get('direccion', ''),
            'telefono' => $configuracion->get('telefono', ''),
            'mensajeTicket' => $configuracion->get('mensaje_ticket', ''),
            'imprimirAutomatico' => $configuracion->get('imprimir_automatico', '0') === '1',
        ]);
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esCajero = ! $usuario->can('ver-todas-las-ventas');

        $base = function () use ($request, $usuario, $esCajero) {
            $consulta = Venta::query();

            if ($esCajero) {
                $consulta->where('user_id', $usuario->id)->whereDate('fecha', today());
            } else {
                $consulta
                    ->when($request->input('desde', today()->toDateString()), fn ($c, $desde) => $c->whereDate('fecha', '>=', $desde))
                    ->when($request->input('hasta', today()->toDateString()), fn ($c, $hasta) => $c->whereDate('fecha', '<=', $hasta))
                    ->when($request->input('cajero_id'), fn ($c, $id) => $c->where('user_id', $id))
                    ->when($request->input('metodo_pago'), fn ($c, $m) => $c->where('metodo_pago', $m))
                    ->when($request->input('estado'), fn ($c, $e) => $c->where('estado', $e))
                    ->when($request->input('numero'), function ($c, $numero): void {
                        $c->where('id', (int) ltrim($numero, '#'));
                    });
            }

            return $consulta;
        };

        $ventas = $base()->with('usuario')->withCount('detalles')->orderByDesc('id')->paginate(30)->withQueryString();

        $resumen = [
            'completadas' => $base()->where('estado', 'COMPLETADA')->count(),
            'total' => $base()->where('estado', 'COMPLETADA')->sum('total'),
            'anuladas' => $base()->where('estado', 'ANULADA')->count(),
        ];

        return view('ventas.index', [
            'ventas' => $ventas,
            'resumen' => $resumen,
            'esCajero' => $esCajero,
            'cajeros' => $esCajero ? [] : User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
            'metodos' => MetodoPago::cases(),
        ]);
    }

    public function ver(Venta $venta): View
    {
        $this->autorizarVerVenta($venta);

        $venta->load(['detalles', 'usuario', 'anuladaPor']);

        return view('ventas.ver', ['venta' => $venta]);
    }

    public function anular(AnularVentaRequest $request, Venta $venta, VentaService $servicio): RedirectResponse
    {
        try {
            $servicio->anular($venta, $request->input('motivo'), $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('ventas.ver', $venta)
            ->with('success', "Venta {$venta->numero()} anulada y stock devuelto.");
    }

    protected function autorizarVerVenta(Venta $venta): void
    {
        $usuario = request()->user();

        if (! $usuario->can('ver-todas-las-ventas') && $venta->user_id !== $usuario->id) {
            abort(403);
        }
    }
}
