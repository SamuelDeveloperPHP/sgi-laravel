<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnviarRevisaoEscopoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-escopo');
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
