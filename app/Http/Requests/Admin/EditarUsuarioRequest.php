<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-usuarios');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'rol' => ['required', Rule::in(['admin', 'encargado', 'cajero'])],
        ];
    }
}
