<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFornecedorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->user()->is_master_admin) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage-fornecedores');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->when(
                    !$this->user()?->is_master_admin,
                    fn ($query) => $query->where('id', $this->user()->company_id)
                ),
            ],
            'razao_social' => 'required|string|max:255',
            'cnpj_cpf' => 'nullable|string|max:20',
            'categoria' => 'nullable|string|max:255',
            'criticidade' => 'nullable|in:alta,media,baixa',
            'status_homologacao' => 'nullable|in:pendente,aprovado,reprovado,inativo',
            'contato_nome' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:20',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'observacoes' => 'nullable|string',
        ];
    }
}
