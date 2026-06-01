<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjetoRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomeProjeto'         => 'required|string|max:255',
            'descricao'           => 'nullable|string',
            'data_inicio'         => 'nullable|date',
            'data_fim'            => 'nullable|date',
            'porc_concluido'      => 'nullable|integer|min:0|max:100',
            'ativo'               => 'nullable|boolean',
            'privacidade'         => 'nullable|string',
            'tags'                => 'nullable|array',
            'responsavel_id'      => ['nullable', $this->tenantScopedExists('users')],
            'membros'             => 'nullable|array',
            'membros.*'           => [$this->tenantScopedExists('users')],
            'imagem_capa'         => 'nullable|image|max:2048',
            'arquivos_anexos'     => 'nullable|array',
            'arquivos_anexos.*'   => 'nullable|file|max:10240',
        ];
    }
}
