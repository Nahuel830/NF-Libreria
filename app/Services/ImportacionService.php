<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportacionService
{
    /**
     * @var string[]
     */
    protected array $encabezados = [
        'codigo', 'nombre', 'categoria', 'marca', 'unidad',
        'precio_compra', 'precio_venta', 'stock_inicial', 'stock_minimo', 'controla_stock',
        'codigo_barras',
    ];

    /**
     * @var string[]
     */
    protected array $unidades = ['unidad', 'paquete', 'caja', 'resma', 'hoja', 'docena'];

    public function __construct(
        protected StockService $stock,
        protected AuditoriaService $auditoria
    ) {}

    public function plantilla(): string
    {
        $lineas = [
            implode(';', $this->encabezados),
            'CUA-001;Cuaderno universitario 100 hojas;Cuadernos;Norma;unidad;8,50;12,00;20;5;si;',
            'LAP-001;Lapicero azul;Lapiceros;Bic;unidad;1,50;2,50;100;20;si;7501000100018',
        ];

        return "\xEF\xBB\xBF".implode("\r\n", $lineas)."\r\n";
    }

    /**
     * Analiza el archivo sin guardar nada.
     *
     * @return array{filas: array<int, array{linea: int, datos: array<string, string>, resultado: string, error: ?string}>, resumen: array<string, int>}
     */
    public function vistaPrevia(string $ruta, string $siExiste, bool $crearCategorias): array
    {
        $crudo = file_get_contents($ruta);

        if (str_starts_with($crudo, "\xEF\xBB\xBF")) {
            $crudo = substr($crudo, 3);
        }

        if (! mb_check_encoding($crudo, 'UTF-8')) {
            $crudo = mb_convert_encoding($crudo, 'UTF-8', 'Windows-1252');
        }

        $lineas = preg_split('/\r\n|\n|\r/', $crudo);
        $primera = $lineas[0] ?? '';
        $separador = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';

        $filas = [];
        $vistos = [];
        $numero = 0;

        foreach ($lineas as $texto) {
            $numero++;

            if (trim($texto) === '') {
                continue;
            }

            $columnas = array_map('trim', str_getcsv($texto, $separador));

            if ($numero === 1 && $this->esEncabezado($columnas)) {
                continue;
            }

            $filas[] = $this->analizarFila($numero, $columnas, $vistos, $siExiste, $crearCategorias);
        }

        $resumen = ['nuevos' => 0, 'actualizar' => 0, 'omitir' => 0, 'errores' => 0];

        foreach ($filas as $fila) {
            match ($fila['resultado']) {
                'Nuevo' => $resumen['nuevos']++,
                'Actualizar' => $resumen['actualizar']++,
                'Omitir' => $resumen['omitir']++,
                default => $resumen['errores']++,
            };
        }

        return ['filas' => $filas, 'resumen' => $resumen];
    }

    /**
     * Importa las filas válidas en UNA transacción.
     *
     * @param  array<int, array{linea: int, datos: array<string, string>, resultado: string, error: ?string}>  $filas
     * @return array<string, int>
     */
    public function importar(array $filas, string $siExiste, bool $crearCategorias, User $usuario): array
    {
        return DB::transaction(function () use ($filas, $siExiste, $crearCategorias) {
            $resumen = ['nuevos' => 0, 'actualizados' => 0, 'omitidos' => 0, 'errores' => 0];

            foreach ($filas as $fila) {
                if ($fila['resultado'] === 'Error') {
                    $resumen['errores']++;
                    continue;
                }

                if ($fila['resultado'] === 'Omitir') {
                    $resumen['omitidos']++;
                    continue;
                }

                $datos = $fila['datos'];
                $categoria = $this->resolverCategoria($datos['categoria'], $crearCategorias);

                if (! $categoria) {
                    $resumen['errores']++;
                    continue;
                }

                if ($fila['resultado'] === 'Actualizar') {
                    $producto = Producto::where('codigo', $datos['codigo'])->first();

                    if (! $producto) {
                        $resumen['errores']++;
                        continue;
                    }

                    $producto->forceFill([
                        'nombre' => $datos['nombre'],
                        'codigo_barras' => $datos['codigo_barras'] !== '' ? $datos['codigo_barras'] : null,
                        'categoria_id' => $categoria->id,
                        'marca' => $datos['marca'] !== '' ? $datos['marca'] : null,
                        'unidad' => $datos['unidad'],
                        'precio_compra' => $datos['precio_compra'],
                        'precio_venta' => $datos['precio_venta'],
                        'stock_minimo' => (int) $datos['stock_minimo'],
                        'controla_stock' => $datos['controla_stock'] === '1',
                    ])->save();

                    $resumen['actualizados']++;
                    continue;
                }

                $producto = Producto::create([
                    'codigo' => $datos['codigo'],
                    'codigo_barras' => $datos['codigo_barras'] !== '' ? $datos['codigo_barras'] : null,
                    'nombre' => $datos['nombre'],
                    'categoria_id' => $categoria->id,
                    'marca' => $datos['marca'] !== '' ? $datos['marca'] : null,
                    'unidad' => $datos['unidad'],
                    'precio_compra' => $datos['precio_compra'],
                    'precio_venta' => $datos['precio_venta'],
                    'stock_minimo' => (int) $datos['stock_minimo'],
                    'controla_stock' => $datos['controla_stock'] === '1',
                ]);

                if ($datos['controla_stock'] === '1' && (int) $datos['stock_inicial'] > 0) {
                    $this->stock->mover($producto->id, (int) $datos['stock_inicial'], 'INICIAL', 'Importación inicial');
                }

                $resumen['nuevos']++;
            }

            $this->auditoria->registrar(
                'CREAR',
                "Importación de productos: {$resumen['nuevos']} nuevos, {$resumen['actualizados']} actualizados, {$resumen['omitidos']} omitidos, {$resumen['errores']} con error.",
                'importacion',
                null,
                $resumen
            );

            return $resumen;
        });
    }

    /**
     * @param  array<int, string>  $vistos
     * @return array{linea: int, datos: array<string, string>, resultado: string, error: ?string}
     */
    protected function analizarFila(int $numero, array $columnas, array &$vistos, string $siExiste, bool $crearCategorias): array
    {
        $datos = array_combine($this->encabezados, array_pad($columnas, count($this->encabezados), ''));

        if (count($columnas) > count($this->encabezados)) {
            return $this->fila($numero, $datos, 'Error', 'La fila tiene más columnas de las esperadas.');
        }

        $datos['codigo'] = mb_strtoupper(trim($datos['codigo']));
        $datos['nombre'] = trim($datos['nombre']);
        $datos['categoria'] = trim($datos['categoria']);
        $datos['codigo_barras'] = trim((string) ($datos['codigo_barras'] ?? ''));

        if ($datos['codigo_barras'] !== '') {
            if (mb_strlen($datos['codigo_barras']) > 50) {
                return $this->fila($numero, $datos, 'Error', 'El código de barras supera 50 caracteres.');
            }

            if (isset($vistos['barras:'.$datos['codigo_barras']])) {
                return $this->fila($numero, $datos, 'Error', 'Código de barras duplicado dentro del archivo.');
            }

            $vistos['barras:'.$datos['codigo_barras']] = true;
        }

        if ($datos['codigo'] === '') {
            return $this->fila($numero, $datos, 'Error', 'Falta el código.');
        }

        if (isset($vistos[$datos['codigo']])) {
            return $this->fila($numero, $datos, 'Error', 'Código duplicado dentro del archivo.');
        }

        $vistos[$datos['codigo']] = true;

        if ($datos['nombre'] === '') {
            return $this->fila($numero, $datos, 'Error', 'Falta el nombre.');
        }

        if ($datos['categoria'] === '') {
            return $this->fila($numero, $datos, 'Error', 'Falta la categoría.');
        }

        $categoriaExiste = Categoria::whereRaw('lower(nombre) = ?', [mb_strtolower($datos['categoria'])])->exists();

        if (! $categoriaExiste && ! $crearCategorias) {
            return $this->fila($numero, $datos, 'Error', "La categoría '{$datos['categoria']}' no existe.");
        }

        $datos['unidad'] = mb_strtolower(trim($datos['unidad']));

        if ($datos['unidad'] === '') {
            $datos['unidad'] = 'unidad';
        }

        if (! in_array($datos['unidad'], $this->unidades, true)) {
            return $this->fila($numero, $datos, 'Error', "Unidad '{$datos['unidad']}' inválida.");
        }

        $compra = $this->parsearPrecio($datos['precio_compra'], true);

        if ($compra === null) {
            return $this->fila($numero, $datos, 'Error', 'Precio de compra inválido.');
        }

        $venta = $this->parsearPrecio($datos['precio_venta'], false);

        if ($venta === null) {
            return $this->fila($numero, $datos, 'Error', 'Precio de venta inválido.');
        }

        $datos['precio_compra'] = $compra;
        $datos['precio_venta'] = $venta;

        foreach (['stock_inicial' => 'Stock inicial', 'stock_minimo' => 'Stock mínimo'] as $campo => $etiqueta) {
            $valor = trim($datos[$campo]);

            if ($valor === '') {
                $datos[$campo] = '0';
                continue;
            }

            if (! preg_match('/^\d+$/', $valor)) {
                return $this->fila($numero, $datos, 'Error', "{$etiqueta} inválido: debe ser un entero mayor o igual a 0.");
            }

            $datos[$campo] = (string) (int) $valor;
        }

        $datos['controla_stock'] = $this->parsearControla($datos['controla_stock']) ? '1' : '0';

        $existe = Producto::where('codigo', $datos['codigo'])->first();

        if ($datos['codigo_barras'] !== '') {
            $otro = Producto::where('codigo_barras', $datos['codigo_barras']);

            if ($existe) {
                $otro->where('id', '!=', $existe->id);
            }

            if ($otro->exists()) {
                return $this->fila($numero, $datos, 'Error', 'El código de barras ya está en uso.');
            }
        }

        if ($existe) {
            return $this->fila($numero, $datos, $siExiste === 'actualizar' ? 'Actualizar' : 'Omitir', null);
        }

        return $this->fila($numero, $datos, 'Nuevo', null);
    }

    /**
     * @param  array<int, string>  $columnas
     */
    protected function esEncabezado(array $columnas): bool
    {
        $normalizadas = array_map(fn ($c) => mb_strtolower(trim($c)), $columnas);
        $esperados = $this->encabezados;

        return $normalizadas === $esperados
            || $normalizadas === array_slice($esperados, 0, 10);
    }

    protected function parsearPrecio(string $valor, bool $permiteVacio): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return $permiteVacio ? '0.00' : null;
        }

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        if (! is_numeric($valor) || (float) $valor < 0) {
            return null;
        }

        return number_format((float) $valor, 2, '.', '');
    }

    protected function parsearControla(string $valor): bool
    {
        $valor = mb_strtolower(trim($valor));

        if ($valor === '') {
            return true;
        }

        return in_array($valor, ['si', 'sí', 's', '1', 'verdadero', 'true', 'y', 'yes'], true);
    }

    protected function resolverCategoria(string $nombre, bool $crear): ?Categoria
    {
        $categoria = Categoria::whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])->first();

        if ($categoria || ! $crear) {
            return $categoria;
        }

        return Categoria::create(['nombre' => $nombre]);
    }

    /**
     * @param  array<string, string>  $datos
     * @return array{linea: int, datos: array<string, string>, resultado: string, error: ?string}
     */
    protected function fila(int $linea, array $datos, string $resultado, ?string $error): array
    {
        return ['linea' => $linea, 'datos' => $datos, 'resultado' => $resultado, 'error' => $error];
    }
}
