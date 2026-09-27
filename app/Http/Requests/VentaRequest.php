<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('realizar-ventas');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'uuid'],
            'metodo_pago' => ['required', Rule::in(array_column(MetodoPago::cases(), 'value'))],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'monto_recibido' => ['nullable', 'numeric', 'min:0'],
            'cliente_nombre' => ['nullable', 'string', 'max:150'],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'es_contingencia' => ['nullable', 'boolean'],
            'fecha_contingencia' => [
                'nullable',
                'date',
                'required_if:es_contingencia,1,true',
                'before_or_equal:now',
                'after_or_equal:'.now()->subDays(7)->toDateTimeString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'La venta debe tener al menos un producto.',
            'items.min' => 'La venta debe tener al menos un producto.',
            'fecha_contingencia.required_if' => 'Indica la fecha y hora real de la venta en papel.',
            'fecha_contingencia.before_or_equal' => 'La fecha de contingencia no puede ser futura.',
            'fecha_contingencia.after_or_equal' => 'La fecha de contingencia no puede ser de más de 7 días atrás.',
        ];
    }
}
