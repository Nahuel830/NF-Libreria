<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Http\Requests\DevolucionRequest;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\DevolucionService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DevolucionController extends Controller
{
    public function crear(Venta $venta): View
    {
        abort_unless($venta->estado === 'COMPLETADA', 404);
        $venta->load('detalles');

        $disponibles = $venta->detalles->map(function ($detalle) {
            $detalle->devuelto = $detalle->devoluciones()->sum('cantidad');
            $detalle->disponible = $detalle->cantidad - $detalle->devuelto;

            return $detalle;
        });

        return view('devoluciones.crear', [
            'venta' => $venta,
            'detalles' => $disponibles,
            'metodos' => MetodoPago::cases(),
        ]);
    }

    public function guardar(DevolucionRequest $request, Venta $venta, DevolucionService $servicio): RedirectResponse
    {
        try {
            $devolucion = $servicio->devolver(
                $venta,
                $request->input('items'),
                $request->input('motivo'),
                $request->input('metodo_reembolso'),
                $request->user()
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['devolucion' => $e->getMessage()]);
        } catch (AuthorizationException $e) {
            return back()->withInput()->withErrors(['devolucion' => $e->getMessage() ?: 'No tienes permiso.']);
        }

        return redirect()->route('devoluciones.ticket', $devolucion)
            ->with('success', "Devolución {$devolucion->numero()} registrada por Bs. {$devolucion->total_devuelto}.");
    }

    public function ticket(Devolucion $devolucion): View
    {
        $devolucion->load(['detalles.producto', 'venta', 'usuario']);

        return view('devoluciones.ticket', [
            'devolucion' => $devolucion,
            'nombreNegocio' => app(\App\Services\ConfiguracionService::class)->get('nombre_negocio', 'NF Librería'),
        ]);
    }
}
