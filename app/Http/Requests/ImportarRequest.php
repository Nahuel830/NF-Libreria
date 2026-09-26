<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-productos');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'si_existe' => ['required', 'in:omitir,actualizar'],
            'crear_categorias' => ['nullable', 'boolean'],
        ];
    }
}
