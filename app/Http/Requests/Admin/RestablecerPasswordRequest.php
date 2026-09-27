<?php

namespace App\Http\Requests\Admin;

use App\Rules\PasswordNoTrivial;
use Illuminate\Foundation\Http\FormRequest;

class RestablecerPasswordRequest extends FormRequest
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
        $rol = $this->route('usuario')?->rol?->value ?? 'cajero';
        $minimo = $rol === 'cajero' ? 8 : 10;

        return [
            'password' => ['required', 'string', "min:{$minimo}", 'confirmed', new PasswordNoTrivial($this->route('usuario')?->usuario)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
