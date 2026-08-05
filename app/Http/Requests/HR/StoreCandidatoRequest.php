<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidatoRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'processo_seletivo_id' => ['required', $this->tenantScopedExists('processos_seletivos')],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'idade' => ['nullable', 'integer', 'min:14', 'max:100'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade_estado' => ['nullable', 'string', 'max:255'],
            'nivel_ensino' => ['nullable', 'string', 'max:255'],
            'faculdade' => ['nullable', 'string', 'max:255'],
            'experiencia_anos' => ['nullable', 'integer', 'min:0'],
            'ultima_empresa' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'tempo_ultimo_emprego' => ['nullable', 'integer', 'min:0'],
            'avaliacao_geral' => ['nullable', 'string'],
            'referencias' => ['nullable', 'string'],
            'etapa_atual' => ['required', 'string', 'in:Triagem de Currículo,Teste Prático,Dinâmica de Grupo,Entrevista Inicial,Entrevista com Gerentes,Entrevista Final,Aprovado,Reprovado'],
        ];
    }
}
