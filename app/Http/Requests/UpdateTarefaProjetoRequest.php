<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTarefaProjetoRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'              => 'sometimes|required|string|max:255',
            'status'            => 'sometimes|required|string|max:45',
            'progresso'         => 'sometimes|required|integer',
            'kanban_coluna_id'  => ['sometimes', 'nullable', $this->tenantScopedExists('kanban_colunas')],
        ];
    }
}
