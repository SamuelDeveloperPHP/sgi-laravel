<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarRascunhoEscopoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-escopo');
    }

    public function rules()
    {
        return [
            'conteudo' => 'nullable|string',
            'revisor_id' => 'nullable|exists:users,id',
            'aprovador_id' => 'nullable|exists:users,id',
        ];
    }
}
