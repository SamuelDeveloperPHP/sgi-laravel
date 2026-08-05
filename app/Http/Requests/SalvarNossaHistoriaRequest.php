<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarNossaHistoriaRequest extends FormRequest
{
    public function authorize()
    {
        $user = auth()->user();

        return $user && ($user->is_master_admin || $user->can('manage-nossa-historia'));
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'conteudo' => 'nullable|string'
        ];
    }
}
