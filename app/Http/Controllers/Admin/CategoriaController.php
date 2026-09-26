<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoriaRequest;
use App\Models\Categoria;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $categorias = Categoria::query()
            ->withCount('productos')
            ->when($request->input('q'), fn ($consulta, $q) => $consulta->where('nombre', 'ilike', "%{$q}%"))
            ->when($request->input('estado') !== null && $request->input('estado') !== '', fn ($consulta) => $consulta->where('activo', $request->boolean('estado')))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('categorias.index', ['categorias' => $categorias]);
    }

    public function crear(): View
    {
        return view('categorias.crear');
    }

    public function guardar(CategoriaRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $categoria = Categoria::create([
            'nombre' => trim($request->input('nombre')),
            'descripcion' => $request->input('descripcion'),
            'activo' => true,
        ]);

        $auditoria->registrar('CREAR', "Se creó la categoría '{$categoria->nombre}'.", $categoria, null, $categoria->toArray());

        return redirect()->route('categorias.index')->with('success', "Categoría '{$categoria->nombre}' creada.");
    }

    public function editar(Categoria $categoria): View
    {
        return view('categorias.editar', ['categoria' => $categoria]);
    }

    public function actualizar(CategoriaRequest $request, Categoria $categoria, AuditoriaService $auditoria): RedirectResponse
    {
        $antes = $categoria->toArray();

        $categoria->fill([
            'nombre' => trim($request->input('nombre')),
            'descripcion' => $request->input('descripcion'),
        ])->save();

        $auditoria->registrar('EDITAR', "Se editó la categoría '{$categoria->nombre}'.", $categoria, $antes, $categoria->fresh()->toArray());

        return redirect()->route('categorias.index')->with('success', "Categoría '{$categoria->nombre}' actualizada.");
    }

    public function estado(Request $request, Categoria $categoria, AuditoriaService $auditoria): RedirectResponse
    {
        $categoria->forceFill(['activo' => ! $categoria->activo])->save();

        $accion = $categoria->activo ? 'ACTIVAR' : 'DESACTIVAR';
        $auditoria->registrar(
            $accion,
            "Se ".($categoria->activo ? 'activó' : 'desactivó')." la categoría '{$categoria->nombre}'.",
            $categoria,
            ['activo' => ! $categoria->activo],
            ['activo' => $categoria->activo]
        );

        return redirect()->route('categorias.index')
            ->with('success', "Categoría '{$categoria->nombre}' ".($categoria->activo ? 'activada' : 'desactivada').'.');
    }
}
