<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'actual' => ['required', 'string', 'current_password'],
            'nueva' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
