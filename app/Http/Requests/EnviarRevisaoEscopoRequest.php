<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class EnviarRevisaoEscopoRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-escopo');
    }

    public function rules()
    {
        return [
            'conteudo' => 'required|string',
            'revisor_id' => ['required', $this->tenantScopedExists('users')],
            'aprovador_id' => ['required', $this->tenantScopedExists('users')],
        ];
    }
}
