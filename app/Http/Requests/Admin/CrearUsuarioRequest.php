<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearUsuarioRequest extends FormRequest
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
            'usuario' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._]+$/', Rule::unique('users', 'usuario')],
            'rol' => ['required', Rule::in(['admin', 'encargado', 'cajero'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.regex' => 'El usuario solo puede tener letras minúsculas, números, punto y guion bajo.',
        ];
    }
}
