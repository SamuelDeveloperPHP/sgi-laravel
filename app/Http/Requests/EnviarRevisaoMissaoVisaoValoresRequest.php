<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class EnviarRevisaoMissaoVisaoValoresRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-missao-visao-valores');
    }

    public function rules()
    {
        return [
            'conteudo' => 'required|string',
            'revisor_id' => ['required', $this->tenantScopedExists('users')],
            'aprovador_id' => ['required', $this->tenantScopedExists('users')],
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
