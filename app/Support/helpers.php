<?php

if (! function_exists('bs')) {
    /**
     * Formatea un monto en bolivianos: "Bs. 1.234,50".
     */
    function bs(float|string|null $monto): string
    {
        $monto = $monto === null || $monto === '' ? 0 : (float) $monto;

        return 'Bs. '.number_format($monto, 2, ',', '.');
    }
}
