<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Http\Requests\VentaRequest;
use App\Models\Venta;
use App\Services\ConfiguracionService;
use App\Services\VentaService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function nueva(): View
    {
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
                $request->only(['token', 'metodo_pago', 'descuento', 'monto_recibido', 'cliente_nombre', 'observaciones']),
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
        $usuario = request()->user();

        if (! $usuario->can('ver-todas-las-ventas') && $venta->user_id !== $usuario->id) {
            abort(403);
        }

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
}
