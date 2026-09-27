<?php

namespace App\Support;

/**
 * Lee la lista de proxies de confianza desde TRUSTED_PROXIES del .env.
 *
 * OJO: no llamar desde el closure `withMiddleware` de bootstrap/app.php,
 * que se ejecuta antes de que se cargue el .env. Se usa en tiempo de
 * request (middleware ConfiarProxies).
 */
final class ProxiesDeConfianza
{
    /**
     * Devuelve null si no hay ninguno configurado (vacío = ninguno).
     *
     * @return array<int, string>|string|null
     */
    public static function lista(?string $crudo = null): array|string|null
    {
        $crudo ??= (string) env('TRUSTED_PROXIES', '');

        $proxies = array_values(array_filter(array_map(
            'trim',
            explode(',', $crudo)
        )));

        if ($proxies === []) {
            return null;
        }

        if ($proxies === ['*']) {
            return '*';
        }

        return $proxies;
    }
}
