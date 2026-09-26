<?php

namespace Database\Seeders;

use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Producto;
use App\Models\User;
use App\Services\VentaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VentasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $vendedores = User::whereIn('rol', [Rol::Cajero, Rol::Encargado])->where('activo', true)->get();

        if ($vendedores->isEmpty()) {
            $this->command->warn('VentasDemoSeeder: no hay cajeros/encargados, se omite.');

            return;
        }

        $productos = Producto::where('activo', true)->get();

        if ($productos->isEmpty()) {
            $this->command->warn('VentasDemoSeeder: no hay productos, se omite.');

            return;
        }

        $metodos = array_column(MetodoPago::cases(), 'value');
        $servicio = app(VentaService::class);
        $creadas = [];

        for ($i = 0; $i < 60; $i++) {
            $vendedor = $vendedores->random();
            Auth::login($vendedor);

            $items = [];
            $lineas = random_int(1, 4);

            for ($j = 0; $j < $lineas; $j++) {
                $items[] = [
                    'producto_id' => $productos->random()->id,
                    'cantidad' => random_int(1, 5),
                ];
            }

            $datos = [
                'token' => (string) Str::uuid(),
                'metodo_pago' => $metodos[array_rand($metodos)],
            ];

            if (in_array($vendedor->rol, [Rol::Admin, Rol::Encargado], true) && random_int(1, 5) === 1) {
                $datos['descuento'] = number_format(random_int(100, 500) / 100, 2, '.', '');
            }

            $diasAtras = random_int(0, 9);
            $fecha = now()->subDays($diasAtras)->setTime(random_int(8, 20), random_int(0, 59));

            try {
                $venta = $servicio->registrar($items, $datos, $vendedor);
            } catch (\Throwable) {
                continue;
            }

            $venta->forceFill(['fecha' => $fecha])->save();
            \App\Models\MovimientoStock::where('referencia_tipo', 'venta')
                ->where('referencia_id', $venta->id)
                ->update(['created_at' => $fecha]);
            $creadas[] = $venta;
        }

        $encargado = User::where('rol', Rol::Encargado)->where('activo', true)->first()
            ?? User::where('rol', Rol::Admin)->where('activo', true)->first();

        if ($encargado && count($creadas) >= 3) {
            Auth::login($encargado);

            foreach (collect($creadas)->random(3) as $venta) {
                try {
                    $servicio->anular($venta->fresh(), 'Venta demo anulada para pruebas', $encargado);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        Auth::logout();
    }
}
