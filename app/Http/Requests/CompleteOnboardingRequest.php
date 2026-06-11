<?php

namespace App\Http\Requests;

use App\Services\CnpjValidator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para conclusão do onboarding — quando o usuário recém
 * registrado cadastra a empresa que será o tenant dele.
 *
 * Regras de segurança:
 *
 * 1. CNPJ DEVE passar pelo checksum oficial dos 14 dígitos. Validação
 *    de regex apenas (formato) NÃO é suficiente — atacante pode
 *    submeter 00.000.000/0000-00 e isso passaria.
 *
 * 2. CNPJ é UNIQUE no banco. Isso bloqueia duas empresas com mesmo
 *    CNPJ — proteção contra falsificação e duplicidade involuntária.
 *
 * 3. Authorization: SEMPRE retorna true porque o usuário precisa
 *    estar autenticado para chegar aqui (route middleware 'auth').
 *    Quem está autenticado pode tentar fazer onboarding — o middleware
 *    RequireCompany impede que reusem este endpoint após já terem
 *    company_id.
 *
 * 4. campos extras prohibidos: bloqueia tentativa de mass-assignment
 *    de company_id direto via body (defesa em profundidade).
 */
class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware já garante usuário autenticado
    }

    /**
     * Normaliza o CNPJ removendo máscara antes da validação para
     * que o "unique" funcione independente do que o usuário digitou.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => CnpjValidator::sanitize((string) $this->input('cnpj')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'razao_social'  => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                'size:14',
                'unique:companies,cnpj',
                function ($attribute, $value, $fail) {
                    if (!CnpjValidator::isValid($value)) {
                        $fail('O CNPJ informado não é válido.');
                    }
                },
            ],
            // Defesa em profundidade contra mass-assignment de campos
            // que jamais devem vir do cliente nesta rota:
            'company_id'      => 'prohibited',
            'is_master_admin' => 'prohibited',
            'status'          => 'prohibited',
        ];
    }

    public function messages(): array
    {
        return [
            'nome_fantasia.required' => 'Informe o nome fantasia da empresa.',
            'razao_social.required'  => 'Informe a razão social.',
            'cnpj.required'          => 'Informe o CNPJ.',
            'cnpj.size'              => 'O CNPJ deve ter exatamente 14 dígitos (sem máscara).',
            'cnpj.unique'            => 'Já existe uma empresa cadastrada com este CNPJ.',
        ];
    }
}
