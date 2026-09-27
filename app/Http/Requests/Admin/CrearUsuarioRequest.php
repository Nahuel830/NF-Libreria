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
        // Admin y encargado: mínimo 10; cajero: mínimo 8.
        $minimo = $this->input('rol') === 'cajero' ? 8 : 10;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._]+$/', Rule::unique('users', 'usuario')],
            'rol' => ['required', Rule::in(['admin', 'encargado', 'cajero'])],
            'password' => ['required', 'string', "min:{$minimo}", 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.regex' => 'El usuario solo puede tener letras minúsculas, números, punto y guion bajo.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
