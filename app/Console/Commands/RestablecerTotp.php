<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Console\Command;

class RestablecerTotp extends Command
{
    protected $signature = 'totp:restablecer
        {usuario : Nombre del usuario (columna usuario)}
        {--motivo= : Motivo del restablecimiento (obligatorio)}';

    protected $description = 'Restablece la verificación en dos pasos de un usuario (contingencia si pierde el teléfono).';

    public function handle(TotpService $totp): int
    {
        $motivo = trim((string) $this->option('motivo'));

        if ($motivo === '') {
            $this->error('Debes indicar --motivo (queda en auditoría).');

            return self::FAILURE;
        }

        $usuario = User::where('usuario', $this->argument('usuario'))->first();

        if (! $usuario) {
            $this->error('No existe ese usuario.');

            return self::FAILURE;
        }

        if (! in_array($usuario->rol, [Rol::Admin, Rol::Encargado], true)) {
            $this->error('Solo admin y encargado usan verificación en dos pasos.');

            return self::FAILURE;
        }

        if (! $totp->activoPara($usuario)) {
            $this->error('Ese usuario no tiene la verificación activada.');

            return self::FAILURE;
        }

        $totp->restablecerPorConsola($usuario, $motivo);

        $this->info("TOTP de '{$usuario->usuario}' restablecido. Deberá configurarlo al entrar.");

        return self::SUCCESS;
    }
}
