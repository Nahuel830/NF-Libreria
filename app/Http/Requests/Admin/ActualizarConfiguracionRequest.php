<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-configuracion');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_negocio' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'mensaje_ticket' => ['required', 'string', 'max:255'],
            'permitir_stock_negativo' => ['nullable', 'boolean'],
            'minutos_inactividad' => ['required', 'integer', 'min:5', 'max:480'],
            'imprimir_automatico' => ['nullable', 'boolean'],
            'exigir_caja_abierta' => ['nullable', 'boolean'],
            'restringir_cajero_por_ip' => ['nullable', 'boolean'],
            'ips_permitidas_cajero' => ['nullable', 'string', 'max:500'],
            'totp_obligatorio_admin' => ['nullable', 'boolean'],
            'totp_obligatorio_encargado' => ['nullable', 'boolean'],
            'forzar_cambio_password' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'quitar_logo' => ['nullable', 'boolean'],
        ];
    }
}
