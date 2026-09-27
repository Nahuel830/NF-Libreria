<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-proveedores');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignorarId = $this->route('proveedor')?->id;

        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('proveedores', 'nombre')->ignore($ignorarId)],
            'nit' => ['nullable', 'string', 'max:20'],
            'contacto' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
