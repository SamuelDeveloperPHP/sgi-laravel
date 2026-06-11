<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnviarRevisaoPoliticaQualidadeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-politica-qualidade');
    }

    public function rules()
    {
        return [
            'conteudo' => 'required|string',
            'revisor_id' => 'required|exists:users,id',
            'aprovador_id' => 'required|exists:users,id',
        ];
    }
}
