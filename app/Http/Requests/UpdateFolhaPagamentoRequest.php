<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolhaPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_proventos' => 'required|numeric|min:0',
            'total_descontos' => 'required|numeric|min:0',
            'total_beneficios' => 'required|numeric|min:0',
        ];
    }
}
