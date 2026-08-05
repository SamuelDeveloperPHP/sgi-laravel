<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'salario_base' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }
}
