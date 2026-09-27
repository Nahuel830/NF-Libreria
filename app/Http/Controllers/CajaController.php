<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbrirCajaRequest;
use App\Http\Requests\CerrarCajaRequest;
use App\Http\Requests\MovimientoCajaRequest;
use App\Models\Caja;
use App\Services\CajaService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CajaController extends Controller
{
    /**
     * @var array<int, string>
     */
    public const DENOMINACIONES = ['200', '100', '50', '20', '10', '5', '2', '1', '0.50', '0.20', '0.10'];

    public function miCaja(Request $request, CajaService $cajas): View
    {
        $caja = Caja::abiertaDe($request->user());

        return view('caja.mi-caja', [
            'caja' => $caja,
            'resumen' => $caja ? $cajas->resumen($caja) : null,
        ]);
    }

    public function abrir(Request $request): View|RedirectResponse
    {
        if (Caja::abiertaDe($request->user())) {
            return redirect()->route('caja.mi-caja');
        }

        return view('caja.abrir');
    }

    public function guardarAbrir(AbrirCajaRequest $request, CajaService $cajas): RedirectResponse
    {
        try {
            $monto = number_format((float) $request->input('monto_inicial'), 2, '.', '');
            $cajas->abrir($request->user(), $monto);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['monto_inicial' => $e->getMessage()]);
        }

        return redirect()->route('ventas.nueva')->with('success', 'Caja abierta.');
    }

    public function movimiento(Caja $caja): View
    {
        $this->autorizarPropia($caja);

        return view('caja.movimiento', ['caja' => $caja]);
    }

    public function guardarMovimiento(MovimientoCajaRequest $request, Caja $caja, CajaService $cajas): RedirectResponse
    {
        $this->autorizarPropia($caja);

        $monto = number_format((float) $request->input('monto'), 2, '.', '');

        if ($request->input('tipo') === 'EGRESO'
            && ! $cajas->puedeEgresar($caja, $monto)
            && ! $request->boolean('confirmar_egreso')) {
            return back()->withInput()->with('warning', "Advertencia: el egreso de Bs. {$monto} supera el efectivo disponible de Bs. {$cajas->efectivoDisponible($caja)}. Revisa y pulsa Guardar de nuevo para confirmar.");
        }

        try {
            $cajas->movimiento($caja, $request->input('tipo'), $monto, trim((string) $request->input('concepto')), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['monto' => $e->getMessage()]);
        }

        return redirect()->route('caja.mi-caja')->with('success', 'Movimiento registrado.');
    }

    public function cerrar(Caja $caja, CajaService $cajas): View
    {
        $this->autorizarPropia($caja);

        if ($caja->estado !== 'ABIERTA') {
            abort(404);
        }

        return view('caja.cerrar', [
            'caja' => $caja,
            'denominaciones' => self::DENOMINACIONES,
            'esperado' => $cajas->efectivoEsperado($caja),
        ]);
    }

    public function guardarCierre(CerrarCajaRequest $request, Caja $caja, CajaService $cajas): RedirectResponse
    {
        $this->autorizarPropia($caja);

        $conteo = [];
        $contado = '0.00';
        $recibidos = $request->input('conteo', []);

        foreach (self::DENOMINACIONES as $denominacion) {
            // Las claves numéricas como '0.50' llegan como 0: se comparan por valor.
            $cantidad = 0;

            foreach ($recibidos as $clave => $valor) {
                if ((float) $clave === (float) $denominacion) {
                    $cantidad = (int) $valor;
                    break;
                }
            }

            if ($cantidad > 0) {
                $conteo[$denominacion] = (string) $cantidad;
                $contado = bcadd($contado, bcmul($denominacion, (string) $cantidad, 2), 2);
            }
        }

        try {
            $cajas->cerrar($caja, $contado, $conteo, $request->input('observaciones'), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['observaciones' => $e->getMessage()]);
        }

        return redirect()->route('caja.ver', $caja)->with('success', 'Caja cerrada.');
    }

    public function historial(Request $request): View
    {
        $cajas = Caja::query()
            ->with(['usuario', 'cerradaPor'])
            ->when($request->input('usuario_id'), fn ($c, $id) => $c->where('user_id', $id))
            ->when($request->input('estado'), fn ($c, $e) => $c->where('estado', $e))
            ->when($request->input('desde'), fn ($c, $d) => $c->whereDate('abierta_en', '>=', $d))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('caja.historial', [
            'cajas' => $cajas,
            'usuarios' => \App\Models\User::orderBy('nombre')->get(['id', 'nombre', 'usuario']),
        ]);
    }

    public function ver(Caja $caja, CajaService $cajas): View
    {
        $caja->load(['usuario', 'cerradaPor', 'movimientos.usuario', 'ventas' => fn ($q) => $q->orderBy('id')]);

        $resumen = $caja->estado === 'ABIERTA' ? $cajas->resumen($caja) : null;

        return view('caja.ver', ['caja' => $caja, 'resumen' => $resumen]);
    }

    protected function autorizarPropia(Caja $caja): void
    {
        $usuario = request()->user();

        if ($caja->user_id !== $usuario->id && ! $usuario->can('ver-todas-las-ventas')) {
            abort(403);
        }
    }
}
