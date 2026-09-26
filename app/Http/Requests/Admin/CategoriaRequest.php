<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-categorias');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignorarId = $this->route('categoria')?->id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                function (string $atributo, mixed $valor, \Closure $fallar) use ($ignorarId): void {
                    $existe = DB::table('categorias')
                        ->whereRaw('lower(nombre) = ?', [mb_strtolower(trim((string) $valor))])
                        ->when($ignorarId, fn ($consulta) => $consulta->where('id', '!=', $ignorarId))
                        ->exists();

                    if ($existe) {
                        $fallar('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
