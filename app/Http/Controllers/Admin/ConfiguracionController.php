<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActualizarConfiguracionRequest;
use App\Services\AuditoriaService;
use App\Services\ConfiguracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    /**
     * @var string[]
     */
    protected array $claves = [
        'nombre_negocio',
        'direccion',
        'telefono',
        'mensaje_ticket',
        'permitir_stock_negativo',
        'minutos_inactividad',
    ];

    public function editar(ConfiguracionService $configuracion): View
    {
        $valores = [];

        foreach ($this->claves as $clave) {
            $valores[$clave] = $configuracion->get($clave, '');
        }

        return view('configuracion.editar', ['valores' => $valores]);
    }

    public function actualizar(
        ActualizarConfiguracionRequest $request,
        ConfiguracionService $configuracion,
        AuditoriaService $auditoria
    ): RedirectResponse {
        $antes = [];

        foreach ($this->claves as $clave) {
            $antes[$clave] = $configuracion->get($clave, '');
        }

        $datos = $request->validated();
        $datos['permitir_stock_negativo'] = $request->boolean('permitir_stock_negativo') ? '1' : '0';

        foreach ($this->claves as $clave) {
            $configuracion->set($clave, $datos[$clave]);
        }

        $despues = [];

        foreach ($this->claves as $clave) {
            $despues[$clave] = $configuracion->get($clave, '');
        }

        $auditoria->registrar(
            'CONFIGURACION',
            'Se actualizó la configuración del sistema.',
            null,
            $antes,
            $despues
        );

        return redirect()->route('configuracion.editar')
            ->with('success', 'Configuración guardada.');
    }
}
