<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Utilidades de red (WEB-1 1.7): IPs exactas y rangos CIDR.
 */
final class Redes
{
    /**
     * @param string[] $permitidas IPs exactas o rangos CIDR.
     */
    public static function ipPermitida(string $ip, array $permitidas): bool
    {
        $permitidas = array_values(array_filter(array_map('trim', $permitidas)));

        if ($permitidas === []) {
            return true;
        }

        return IpUtils::checkIp($ip, $permitidas);
    }

    /**
     * @return string[]
     */
    public static function listaDesdeTexto(string $texto): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,]+/', $texto) ?: []
        )));
    }
}
