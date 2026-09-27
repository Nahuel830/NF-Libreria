<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('anular-ventas');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5'],
            'metodo_reembolso' => ['required', Rule::in(array_column(\App\Enums\MetodoPago::cases(), 'value'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.detalle_venta_id' => ['required', 'integer', 'exists:detalle_ventas,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'La devolución debe tener al menos un producto.',
            'items.min' => 'La devolución debe tener al menos un producto.',
        ];
    }
}
