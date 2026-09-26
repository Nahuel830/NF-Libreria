<?php

namespace App\Http\Requests\Admin;

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
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
