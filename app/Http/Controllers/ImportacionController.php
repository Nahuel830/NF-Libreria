<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportarRequest;
use App\Services\ImportacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportacionController extends Controller
{
    public function plantilla(ImportacionService $servicio): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print($servicio->plantilla()),
            'plantilla-productos.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function importar(): View
    {
        return view('productos.importar');
    }

    public function vistaPrevia(ImportarRequest $request, ImportacionService $servicio): View
    {
        $siExiste = $request->input('si_existe');
        $crearCategorias = $request->boolean('crear_categorias');

        $resultado = $servicio->vistaPrevia(
            $request->file('archivo')->getRealPath(),
            $siExiste,
            $crearCategorias
        );

        $token = (string) Str::uuid();

        Storage::disk('local')->put(
            "importaciones/{$token}.json",
            json_encode(['filas' => $resultado['filas'], 'si_existe' => $siExiste, 'crear_categorias' => $crearCategorias])
        );

        return view('productos.vista-previa', [
            'filas' => $resultado['filas'],
            'resumen' => $resultado['resumen'],
            'token' => $token,
        ]);
    }

    public function confirmar(Request $request, ImportacionService $servicio): View
    {
        $token = (string) $request->input('token', '');
        $ruta = "importaciones/{$token}.json";

        abort_unless($token !== '' && Storage::disk('local')->exists($ruta), 419);

        $pendiente = json_decode(Storage::disk('local')->get($ruta), true);

        $resumen = $servicio->importar(
            $pendiente['filas'],
            $pendiente['si_existe'],
            (bool) $pendiente['crear_categorias'],
            $request->user()
        );

        Storage::disk('local')->delete($ruta);

        return view('productos.resultado', ['resumen' => $resumen]);
    }
}
