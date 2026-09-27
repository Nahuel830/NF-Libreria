<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CajaService
{
    public function __construct(
        protected AuditoriaService $auditoria,
        protected ConfiguracionService $configuracion
    ) {}

    public function exigirAbierta(): bool
    {
        return $this->configuracion->get('exigir_caja_abierta', '1') === '1';
    }

    public function abrir(User $usuario, string $montoInicial): Caja
    {
        if (Caja::abiertaDe($usuario)) {
            throw new DomainException('Ya tienes una caja abierta.');
        }

        $caja = Caja::create([
            'user_id' => $usuario->id,
            'abierta_en' => now(),
            'monto_inicial' => $montoInicial,
            'estado' => 'ABIERTA',
        ]);

        $this->auditoria->registrar(
            'CREAR',
            "El usuario '{$usuario->usuario}' abrió caja con Bs. {$montoInicial}.",
            $caja
        );

        return $caja;
    }

    public function movimiento(Caja $caja, string $tipo, string $monto, string $concepto, User $usuario): void
    {
        if ($caja->estado !== 'ABIERTA') {
            throw new DomainException('La caja está cerrada y no acepta movimientos.');
        }

        $caja->movimientos()->create([
            'tipo' => $tipo,
            'user_id' => $usuario->id,
            'monto' => $monto,
            'concepto' => $concepto,
        ]);

        $this->auditoria->registrar(
            'CREAR',
            ($tipo === 'INGRESO' ? 'Ingreso' : 'Egreso')." de Bs. {$monto} en caja #{$caja->id}: {$concepto}.",
            $caja
        );
    }

    public function puedeEgresar(Caja $caja, string $monto): bool
    {
        return bccomp($monto, $this->efectivoDisponible($caja), 2) <= 0;
    }

    /**
     * @param  array<string, string>  $conteo
     */
    public function cerrar(Caja $caja, string $efectivoContado, array $conteo, ?string $observaciones, User $usuario): Caja
    {
        if ($caja->estado !== 'ABIERTA') {
            throw new DomainException('La caja ya está cerrada.');
        }

        $esperado = $this->efectivoEsperado($caja);
        $diferencia = bcsub($efectivoContado, $esperado, 2);

        if ($diferencia !== '0.00' && trim((string) $observaciones) === '') {
            throw new DomainException('Con diferencia en el arqueo, las observaciones son obligatorias.');
        }

        $puedeAjena = in_array($usuario->rol->value, ['admin', 'encargado'], true);

        if ($caja->user_id !== $usuario->id && ! $puedeAjena) {
            throw new DomainException('No puedes cerrar la caja de otro usuario.');
        }

        $caja->forceFill([
            'estado' => 'CERRADA',
            'cerrada_en' => now(),
            'cerrada_por' => $usuario->id,
            'efectivo_esperado' => $esperado,
            'efectivo_contado' => $efectivoContado,
            'diferencia' => $diferencia,
            'detalle_conteo' => $conteo,
            'observaciones_cierre' => $observaciones,
        ])->save();

        $this->auditoria->registrar(
            'CREAR',
            "Cierre de caja #{$caja->id}: esperado Bs. {$esperado}, contado Bs. {$efectivoContado}, diferencia Bs. {$diferencia}.",
            $caja
        );

        return $caja;
    }

    public function efectivoEsperado(Caja $caja): string
    {
        $ventasEfectivo = $caja->ventas()
            ->where('estado', 'COMPLETADA')
            ->where('metodo_pago', 'EFECTIVO')
            ->sum('total');

        $ingresos = $caja->movimientos()->where('tipo', 'INGRESO')->sum('monto');
        $egresos = $caja->movimientos()->where('tipo', 'EGRESO')->sum('monto');

        return bcsub(bcadd(bcadd($caja->monto_inicial, (string) $ventasEfectivo, 2), (string) $ingresos, 2), (string) $egresos, 2);
    }

    public function efectivoDisponible(Caja $caja): string
    {
        return $this->efectivoEsperado($caja);
    }

    /**
     * @return array<string, string>
     */
    public function totalesPorMetodo(Caja $caja): array
    {
        $totales = ['EFECTIVO' => '0.00', 'QR' => '0.00', 'TRANSFERENCIA' => '0.00', 'TARJETA' => '0.00', 'OTRO' => '0.00'];

        foreach ($caja->ventas()->where('estado', 'COMPLETADA')->selectRaw('metodo_pago, SUM(total) as total')->groupBy('metodo_pago')->get() as $fila) {
            $totales[$fila->metodo_pago] = number_format((float) $fila->total, 2, '.', '');
        }

        return $totales;
    }

    /**
     * @return array<string, mixed>
     */
    public function resumen(Caja $caja): array
    {
        return [
            'esperado' => $this->efectivoEsperado($caja),
            'por_metodo' => $this->totalesPorMetodo($caja),
            'ingresos' => number_format((float) $caja->movimientos()->where('tipo', 'INGRESO')->sum('monto'), 2, '.', ''),
            'egresos' => number_format((float) $caja->movimientos()->where('tipo', 'EGRESO')->sum('monto'), 2, '.', ''),
            'ventas' => $caja->ventas()->where('estado', 'COMPLETADA')->count(),
        ];
    }
}
