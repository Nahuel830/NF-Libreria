<?php

namespace App\Services;

use App\Enums\Rol;
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

    public function __construct(
        protected AuditoriaService $auditoria,
        protected ConfiguracionService $configuracion
    ) {
        $this->google = new Google2FA();
    }

    public function obligatorioPara(User $usuario): bool
    {
        if ($usuario->rol === Rol::Admin) {
            return $this->configuracion->get('totp_obligatorio_admin', '1') === '1';
        }

        if ($usuario->rol === Rol::Encargado) {
            return $this->configuracion->get('totp_obligatorio_encargado', '0') === '1';
        }

        return false;
    }

    public function activoPara(User $usuario): bool
    {
        return $usuario->tieneTotpActivo();
    }

    /**
     * Devuelve el secreto pendiente de confirmar (reutiliza el existente).
     */
    public function generarSecretoPara(User $usuario): string
    {
        if (is_string($usuario->totp_secreto) && $usuario->totp_secreto !== '') {
            return $usuario->totp_secreto;
        }

        $secreto = $this->google->generateSecretKey();
        $usuario->forceFill(['totp_secreto' => $secreto])->save();

        return $secreto;
    }

    public function qrSvg(string $secreto, User $usuario): string
    {
        $url = $this->google->getQRCodeUrl('NF Librería', $usuario->usuario, $secreto);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Verifica el código con ventana ±1 paso y sin reutilizar.
     * Devuelve el paso usado o false.
     */
    public function verificar(User $usuario, string $codigo): int|false
    {
        if (! is_string($usuario->totp_secreto) || $usuario->totp_secreto === '') {
            return false;
        }

        $paso = $this->google->verifyKeyNewer(
            $usuario->totp_secreto,
            trim($codigo),
            $usuario->totp_ultimo_paso ?? -1,
            1
        );

        return $paso === false ? false : (int) $paso;
    }

    /**
     * Confirma la activación y devuelve los códigos de recuperación en claro.
     *
     * @return string[]
     */
    public function confirmar(User $usuario, int $paso): array
    {
        $planos = $this->nuevosCodigosPlanos();

        $usuario->forceFill([
            'totp_confirmado_en' => now(),
            'totp_ultimo_paso' => $paso,
            'codigos_recuperacion' => array_map(fn ($c) => Hash::make($c), $planos),
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

    public function marcarPasoUsado(User $usuario, int $paso): void
    {
        $usuario->forceFill(['totp_ultimo_paso' => $paso])->save();
    }

    /**
     * @return string[]
     */
    protected function nuevosCodigosPlanos(): array
    {
        $planos = [];

        for ($i = 0; $i < 10; $i++) {
            $planos[] = Str::upper(Str::random(10));
        }

        return $planos;
    }

    /**
     * Regenera los códigos y devuelve los nuevos en claro (mostrar una sola vez).
     *
     * @return string[]
     */
    public function regenerarCodigos(User $usuario): array
    {
        $planos = $this->nuevosCodigosPlanos();

        $usuario->forceFill([
            'codigos_recuperacion' => array_map(fn ($c) => Hash::make($c), $planos),
        ])->save();

        $this->auditoria->registrar(
            'TOTP',
            "El usuario '{$usuario->usuario}' regeneró sus códigos de recuperación.",
            $usuario,
            null,
            null,
            $usuario
        );

        return $planos;
    }

    public function usarCodigoRecuperacion(User $usuario, string $codigo): bool
    {
        $codigo = trim($codigo);
        $restantes = $usuario->codigos_recuperacion ?? [];

        foreach ($restantes as $i => $hash) {
            if (Hash::check($codigo, $hash)) {
                unset($restantes[$i]);
                $usuario->forceFill(['codigos_recuperacion' => array_values($restantes)])->save();

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
        $this->limpiar($usuario);

        $this->auditoria->registrar(
            'TOTP',
            "El usuario '{$usuario->usuario}' desactivó la verificación en dos pasos.",
            $usuario
        );
    }

    public function restablecerPorAdmin(User $usuario, User $admin): void
    {
        $this->limpiar($usuario);

        $this->auditoria->registrar(
            'TOTP',
            "El administrador '{$admin->usuario}' restableció la verificación en dos pasos de '{$usuario->usuario}'.",
            $usuario,
            null,
            null,
            $admin
        );
    }

    public function restablecerPorConsola(User $usuario, string $motivo): void
    {
        $this->limpiar($usuario);

        $this->auditoria->registrar(
            'TOTP',
            "Verificación en dos pasos de '{$usuario->usuario}' restablecida por consola. Motivo: {$motivo}",
            $usuario
        );
    }

    protected function limpiar(User $usuario): void
    {
        $usuario->forceFill([
            'totp_secreto' => null,
            'totp_confirmado_en' => null,
            'totp_ultimo_paso' => null,
            'codigos_recuperacion' => null,
        ])->save();
    }
}
