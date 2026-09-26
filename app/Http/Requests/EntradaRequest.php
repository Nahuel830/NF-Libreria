<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('registrar-entradas');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proveedor' => ['nullable', 'string', 'max:150'],
            'documento_referencia' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string'],
            'actualizar_precio_compra' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.costo_unitario' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'La entrada debe tener al menos un ítem.',
            'items.min' => 'La entrada debe tener al menos un ítem.',
        ];
    }
}
