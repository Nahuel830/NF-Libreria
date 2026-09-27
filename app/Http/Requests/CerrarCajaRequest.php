<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarCajaRequest extends FormRequest
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
            'conteo' => ['nullable', 'array'],
            'conteo.*' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
