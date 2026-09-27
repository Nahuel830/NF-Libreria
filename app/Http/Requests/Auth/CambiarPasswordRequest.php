<?php

namespace App\Http\Requests\Auth;

use App\Enums\Rol;
use App\Rules\PasswordNoTrivial;
use Illuminate\Foundation\Http\FormRequest;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, [Rol::Admin, Rol::Encargado], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minimo = $this->user()?->rol?->value === 'cajero' ? 8 : 10;

        return [
            'actual' => ['required', 'string', 'current_password'],
            'nueva' => ['required', 'string', "min:{$minimo}", 'confirmed', new PasswordNoTrivial($this->user()?->usuario)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nueva.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
