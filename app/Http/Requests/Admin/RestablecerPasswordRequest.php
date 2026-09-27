<?php

namespace App\Http\Requests\Admin;

use App\Rules\PasswordNoTrivial;
use App\Services\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
        $negocio = app(ConfiguracionService::class)->get('nombre_negocio', '');

        return [
            'password' => ['required', 'string', Password::defaults(), 'confirmed', new PasswordNoTrivial($this->route('usuario')?->usuario, $negocio)],
        ];
    }
}
