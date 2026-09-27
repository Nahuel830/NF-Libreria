<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordNoTrivial;
use App\Services\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $negocio = app(ConfiguracionService::class)->get('nombre_negocio', '');

        return [
            'actual' => ['required', 'string', 'current_password'],
            'nueva' => ['required', 'string', Password::defaults(), 'confirmed', new PasswordNoTrivial($this->user()?->usuario, $negocio)],
        ];
    }
}
