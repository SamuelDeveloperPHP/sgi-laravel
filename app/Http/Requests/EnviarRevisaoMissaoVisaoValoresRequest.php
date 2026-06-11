<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnviarRevisaoMissaoVisaoValoresRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-missao-visao-valores');
    }

    public function rules()
    {
        return [
            'conteudo' => 'required|string',
            'revisor_id' => 'required|exists:users,id',
            'aprovador_id' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'revisor_id.required' => 'Você precisa selecionar um Revisor.',
            'aprovador_id.required' => 'Você precisa selecionar um Aprovador.',
        ];
    }
}
