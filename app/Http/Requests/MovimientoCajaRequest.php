<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoCajaRequest extends FormRequest
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
            'tipo' => ['required', Rule::in(['INGRESO', 'EGRESO'])],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'concepto' => ['required', 'string', 'max:200'],
        ];
    }
}
