<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarStockRequest;
use App\Models\Producto;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AjusteStockController extends Controller
{
    public function editar(Producto $producto): View
    {
        return view('inventario.ajustar', ['producto' => $producto]);
    }

    public function actualizar(AjustarStockRequest $request, Producto $producto, StockService $stock): RedirectResponse
    {
        $datos = $request->validated();

        $movimiento = $stock->ajustar($producto, (int) $datos['stock_real'], $datos['motivo']);

        if ($movimiento === null) {
            return redirect()->route('productos.ver', $producto)
                ->with('warning', 'El stock ya coincide con el conteo: no se creó ningún movimiento.');
        }

        return redirect()->route('productos.ver', $producto)
            ->with('success', "Stock ajustado a {$datos['stock_real']}.");
    }
}
