<?php

namespace App\Http\Requests;

use App\Services\CnpjValidator;
use App\Rules\CorporateEmail;
use App\Services\CorporateDomain;
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
        $this->merge([
            'cnpj' => CnpjValidator::sanitize((string) $this->input('cnpj')),
            'dominio_corporativo' => CorporateDomain::normalize((string) $this->input('dominio_corporativo')),
            'email_corporativo' => strtolower(trim((string) $this->input('email_corporativo'))),
            'email_administrador' => strtolower(trim((string) $this->input('email_administrador'))),
            'email_recuperacao_secundario' => strtolower(trim((string) $this->input('email_recuperacao_secundario'))),
        ]);
    }

    public function rules(): array
    {
        $domain = (string) $this->input('dominio_corporativo');
        $authenticatedEmail = strtolower((string) $this->user()?->email);

        return [
            // Identificacao
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

            // Endereco (opcional - alguns campos vem da APIBrasil, outros
            // o usuario preenche). Limites condizentes com a migration.
            'cep'          => ['nullable', 'string', 'max:10'],
            'logradouro'   => ['nullable', 'string', 'max:255'],
            'numero'       => ['nullable', 'string', 'max:20'],
            'complemento'  => ['nullable', 'string', 'max:255'],
            'bairro'       => ['nullable', 'string', 'max:255'],
            'cidade'       => ['nullable', 'string', 'max:255'],
            'estado'       => ['nullable', 'string', 'size:2'],

            // Contato corporativo
            'dominio_corporativo' => ['required', 'string', 'max:253'],
            'email_corporativo' => ['required', 'email:rfc', 'max:255', new CorporateEmail($domain)],
            'telefone'          => ['nullable', 'string', 'max:20'],

            // O usuario verificado que conclui o fluxo e o administrador.
            // Uma conta nao pode indicar terceiro e ganhar o papel de admin.
            'email_administrador' => [
                'required', 'email:rfc', 'max:255', new CorporateEmail($domain),
                function ($attribute, $value, $fail) use ($authenticatedEmail) {
                    if (!hash_equals($authenticatedEmail, strtolower((string) $value))) {
                        $fail('O e-mail do administrador deve ser o mesmo da conta verificada.');
                    }
                },
            ],
            'email_recuperacao_secundario' => [
                'required', 'email:rfc', 'max:255', 'different:email_administrador',
                new CorporateEmail($domain),
            ],

            // Observacoes
            'observacoes' => ['nullable', 'string', 'max:5000'],

            // Defesa em profundidade contra mass-assignment de campos
            // que jamais devem vir do cliente nesta rota:
            'company_id'           => 'prohibited',
            'is_master_admin'      => 'prohibited',
            'status'               => 'prohibited',
            'nome_administrador'   => 'prohibited', // setado pelo backend
            'criterios_avaliacao_fornecedor' => 'prohibited',
        ];
    }

    public function messages(): array
    {
        return [
            'nome_fantasia.required'   => 'Informe o nome fantasia da empresa.',
            'razao_social.required'    => 'Informe a razão social.',
            'cnpj.required'            => 'Informe o CNPJ.',
            'cnpj.size'                => 'O CNPJ deve ter exatamente 14 dígitos (sem máscara).',
            'cnpj.unique'              => 'Já existe uma empresa cadastrada com este CNPJ.',
            'estado.size'              => 'A UF deve ter exatamente 2 letras (ex: SP, RJ).',
            'email_corporativo.email'  => 'Informe um e-mail corporativo válido.',
            'dominio_corporativo.required' => 'Informe o dominio oficial da empresa.',
            'email_administrador.required' => 'Informe o e-mail do administrador.',
            'email_recuperacao_secundario.required' => 'Informe um segundo e-mail de recuperacao.',
            'email_recuperacao_secundario.different' => 'O segundo e-mail de recuperacao deve ser diferente do administrador.',
        ];
    }
}
