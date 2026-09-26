<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularVentaRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['motivo' => 'motivo'];
    }
}
