<?php

return [
    /*
     * Cabeceras de seguridad (middleware CabecerasSeguridad, WEB-1 1.3).
     * En emergencia se puede desactivar la CSP con CSP_ACTIVA=false
     * o pasarla a solo-reporte con CSP_SOLO_REPORTE=true.
     */
    'csp_activa' => filter_var(env('CSP_ACTIVA', true), FILTER_VALIDATE_BOOLEAN),
    'csp_solo_reporte' => filter_var(env('CSP_SOLO_REPORTE', false), FILTER_VALIDATE_BOOLEAN),
];
