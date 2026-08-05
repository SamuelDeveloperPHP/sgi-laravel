<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTarefaProjetoRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'projeto_id'        => ['required', $this->tenantScopedExists('sts_projetos')],
            'nome'              => 'required|string|max:255',
            'kanban_coluna_id'  => ['nullable', $this->tenantScopedExists('kanban_colunas')],
            'tags'              => 'sometimes|nullable|array',
            'tags.*'            => 'string|max:50',
            'users'             => 'sometimes|nullable|array',
            'users.*'           => ['integer', $this->tenantScopedExists('users')],
        ];
    }
}
