<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuncionarioRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $funcionario = $this->route('funcionario'); // Model instance due to route model binding
        $companyId = $funcionario->company_id;

        return [
            'company_id' => auth()->user()->is_master_admin ? 'nullable|exists:companies,id' : 'nullable',
            'nome' => 'required|string|max:255',
            'cpf' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('rh_funcionarios')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($funcionario->id)
            ],
            'matricula' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('rh_funcionarios')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($funcionario->id)
            ],
            'data_admissao' => 'nullable|date',
            'dependentes' => 'nullable|integer|min:0',
            'estado_civil' => 'nullable|string|max:50',
            'salario_bruto' => 'nullable|numeric|min:0',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:Ativo,Inativo,Férias,Afastado',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'area_id' => ['nullable', $this->tenantScopedExists('rh_areas')],
            'cargo_id' => ['nullable', $this->tenantScopedExists('rh_cargos')],
            'genero' => 'nullable|in:M,F,O,N',
            'data_demissao' => 'nullable|date',
            'motivo_demissao' => 'nullable|string|max:255',
            'carga_horaria_mensal' => 'nullable|integer|min:0',
            'horario_trabalho' => 'nullable|string|max:255',
            'data_nascimento' => 'nullable|date',
            'rg' => 'nullable|string|max:255',
            'nacionalidade' => 'nullable|string|max:255',
            'titulo_eleitor' => 'nullable|string|max:255',
            'carteira_reservista' => 'nullable|string|max:255',
            'naturalidade' => 'nullable|string|max:255',
            'ctps' => 'nullable|string|max:255',
            'pis' => 'nullable|string|max:255',
            'celular' => 'nullable|string|max:255',
            'nome_mae' => 'nullable|string|max:255',
            'nome_pai' => 'nullable|string|max:255',
            'escolaridade' => 'nullable|string|max:255',
            'tipo_sanguineo' => 'nullable|string|max:255',
            'banco' => 'nullable|string|max:255',
            'agencia' => 'nullable|string|max:255',
            'conta_corrente' => 'nullable|string|max:255',
            'parcelas_ferias' => 'nullable|integer|min:0',
            'data_decimo_terceiro' => 'nullable|string|max:255',
            'parcelas_decimo_terceiro' => 'nullable|integer|min:0',
        ];
    }
}
