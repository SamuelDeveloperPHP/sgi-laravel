<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMapaRiscoRequest extends FormRequest
{
    use TenantScopedRules;

    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->user()->is_master_admin) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function authorize(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->is_master_admin || $user->can('manage-mapas-risco');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'titulo' => 'required|string|max:255',
            'setor' => 'required|string|max:255',
            'aprovador_id' => ['required', $this->tenantScopedExists('users')],
            'data_mapeamento' => 'required|date',
            'pontos_risco' => 'required|array',
            'pontos_risco.*.local_detalhado' => 'required|string|max:255',
            'pontos_risco.*.grupo_risco' => 'required|in:Físico,Químico,Biológico,Ergonômico,Acidentes',
            'pontos_risco.*.agente_risco' => 'required|string|max:255',
            'pontos_risco.*.gravidade' => 'required|in:Pequeno,Médio,Grande',
            'pontos_risco.*.numero_trabalhadores_expostos' => 'required|integer|min:0',
            'pontos_risco.*.medidas_preventivas' => 'nullable|string',
        ];
    }
}
