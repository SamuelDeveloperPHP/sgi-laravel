<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateObjetivoQualidadeRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-objetivos-qualidade');
    }

    public function rules()
    {
        return [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'prazo' => 'required|date',
            'responsaveis' => 'required|array',
            'responsaveis.*' => [$this->tenantScopedExists('users')],
            'revisor_id' => ['nullable', $this->tenantScopedExists('users')],
            'aprovador_id' => ['nullable', $this->tenantScopedExists('users')],
        ];
    }
}
