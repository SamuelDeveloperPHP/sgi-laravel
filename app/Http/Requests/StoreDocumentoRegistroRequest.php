<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoRegistroRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-controle-documentos');
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'identificacao' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'tipo_documento' => 'nullable|string|max:255',
            'revisao_atual' => 'nullable|string|max:255',
            'ano_ultima_revisao' => 'nullable|string|max:255',
            'meio' => 'nullable|string|max:255',
            'local_arquivo' => 'nullable|string|max:255',
            'indexacao' => 'nullable|string|max:255',
            'protecao' => 'nullable|string|max:255',
            'tempo_arquivamento' => 'nullable|string|max:255',
            'destino_apos_prazo' => 'nullable|string|max:255',
        ];
    }
}
