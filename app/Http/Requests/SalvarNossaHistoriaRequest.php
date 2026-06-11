<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarNossaHistoriaRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-nossa-historia');
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'conteudo' => 'nullable|string'
        ];
    }
}
