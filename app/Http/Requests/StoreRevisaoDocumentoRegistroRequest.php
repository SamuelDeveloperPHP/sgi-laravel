<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevisaoDocumentoRegistroRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-controle-documentos');
    }

    public function rules()
    {
        return [
            'revisao' => 'required|string|max:255',
            'data_revisao' => 'required|date',
            'alteracoes' => 'nullable|string',
            'responsavel_id' => 'nullable|exists:users,id',
            'aprovador_id' => 'nullable|exists:users,id',
        ];
    }
}
