<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TotpService
{
    protected Google2FA $google;

    public function __construct(protected AuditoriaService $auditoria)
    {
        $this->google = new Google2FA();
    }

    public function generarSecreto(): string
    {
        return $this->google->generateSecretKey();
    }

    public function qrSvg(string $secreto, User $usuario): string
    {
        $url = $this->google->getQRCodeUrl('NF Librería', $usuario->usuario, $secreto);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($url);
    }

    public function verificar(User $usuario, string $codigo): bool
    {
        if ($usuario->totp_secreto === null) {
            return false;
        }

        return $this->google->verifyKey($usuario->totp_secreto, trim($codigo));
    }

    /**
     * Activa el TOTP y devuelve los códigos de recuperación en claro (mostrar una sola vez).
     *
     * @return string[]
     */
    public function activar(User $usuario, string $secreto): array
    {
        $planos = [];

        for ($i = 0; $i < 8; $i++) {
            $planos[] = Str::upper(Str::random(10));
        }

        $usuario->forceFill([
            'totp_secreto' => $secreto,
            'totp_activo' => true,
            'totp_recuperacion' => array_map(fn ($c) => Hash::make($c), $planos),
        ])->save();

        $this->auditoria->registrar(
            'TOTP',
            "El usuario '{$usuario->usuario}' activó la verificación en dos pasos.",
            $usuario,
            null,
            null,
            $usuario
        );

        return $planos;
    }

    public function verificarRecuperacion(User $usuario, string $codigo): bool
    {
        $codigo = trim($codigo);
        $restantes = $usuario->totp_recuperacion ?? [];

        foreach ($restantes as $i => $hash) {
            if (Hash::check($codigo, $hash)) {
                unset($restantes[$i]);
                $usuario->forceFill(['totp_recuperacion' => array_values($restantes)])->save();

                $this->auditoria->registrar(
                    'TOTP',
                    "El usuario '{$usuario->usuario}' entró con un código de recuperación.",
                    $usuario,
                    null,
                    null,
                    $usuario
                );

                return true;
            }
        }

        return false;
    }

    public function desactivar(User $usuario): void
    {
        $usuario->forceFill([
            'totp_secreto' => null,
            'totp_activo' => false,
            'totp_recuperacion' => null,
        ])->save();

        $this->auditoria->registrar(
            'TOTP',
            "El usuario '{$usuario->usuario}' desactivó la verificación en dos pasos.",
            $usuario
        );
    }
}
