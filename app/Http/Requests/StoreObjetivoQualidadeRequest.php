<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreObjetivoQualidadeRequest extends FormRequest
{
    use TenantScopedRules;

    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->user()->is_master_admin) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

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
            'company_id' => 'nullable|exists:companies,id',
        ];
    }
}
