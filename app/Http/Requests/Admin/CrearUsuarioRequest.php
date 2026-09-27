<?php

namespace App\Http\Requests\Admin;

use App\Rules\PasswordNoTrivial;
use App\Services\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
        $negocio = app(ConfiguracionService::class)->get('nombre_negocio', '');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._]+$/', Rule::unique('users', 'usuario')],
            'rol' => ['required', Rule::in(['admin', 'encargado', 'cajero'])],
            'password' => ['required', 'string', Password::defaults(), 'confirmed', new PasswordNoTrivial($this->input('usuario'), $negocio)],
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
