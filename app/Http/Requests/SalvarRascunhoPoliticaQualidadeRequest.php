<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class SalvarRascunhoPoliticaQualidadeRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-politica-qualidade');
    }

    public function rules()
    {
        return [
            'conteudo' => 'nullable|string',
            'revisor_id' => ['nullable', $this->tenantScopedExists('users')],
            'aprovador_id' => ['nullable', $this->tenantScopedExists('users')],
        ];
    }
}
