<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreRevisaoDocumentoRegistroRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-controle-documentos');
    }

    public function rules()
    {
        return [
            'revisao' => 'required|string|max:255',
            'data_revisao' => 'required|date',
            'alteracoes' => 'nullable|string',
            'responsavel_id' => ['nullable', $this->tenantScopedExists('users')],
            'aprovador_id' => ['nullable', $this->tenantScopedExists('users')],
        ];
    }
}
