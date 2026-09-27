<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-clientes');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignorarId = $this->route('cliente')?->id;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'ci_nit' => ['nullable', 'string', 'max:20', Rule::unique('clientes', 'ci_nit')->ignore($ignorarId)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
