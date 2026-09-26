<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar-stock');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stock_real' => ['required', 'integer'],
            'motivo' => ['required', 'string', 'min:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stock_real' => 'stock real contado',
            'motivo' => 'motivo',
        ];
    }
}
