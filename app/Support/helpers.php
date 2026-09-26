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

if (! function_exists('logo_url')) {
    /**
     * URL del logo del negocio o del logo por defecto.
     */
    function logo_url(): string
    {
        $logo = app(\App\Services\ConfiguracionService::class)->get('logo_negocio');

        if (is_string($logo) && $logo !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)) {
            return asset(\Illuminate\Support\Facades\Storage::url($logo));
        }

        return asset('img/logo.svg');
    }
}
