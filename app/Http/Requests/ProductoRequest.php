<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-productos');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $this->merge([
                'codigo' => mb_strtoupper(trim((string) $this->input('codigo'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignorarId = $this->route('producto')?->id;

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('productos', 'codigo')->ignore($ignorarId)],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('activo', true)],
            'marca' => ['nullable', 'string', 'max:100'],
            'unidad' => ['required', Rule::in(['unidad', 'paquete', 'caja', 'resma', 'hoja', 'docena'])],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'controla_stock' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
        ];
    }

    public function precioCompra(): string
    {
        return number_format((float) ($this->input('precio_compra') ?? 0), 2, '.', '');
    }

    public function precioVenta(): string
    {
        return number_format((float) $this->input('precio_venta'), 2, '.', '');
    }
}
