<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\CorporateEmail;
use App\Services\CnpjValidator;
use App\Services\CorporateDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => CnpjValidator::sanitize((string) $this->input('cnpj')),
            'dominio_corporativo' => CorporateDomain::normalize((string) $this->input('dominio_corporativo')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'email_corporativo' => strtolower(trim((string) $this->input('email_corporativo'))),
            'email_recuperacao_secundario' => strtolower(trim((string) $this->input('email_recuperacao_secundario'))),
            'estado' => strtoupper(trim((string) $this->input('estado'))),
        ]);
    }

    public function rules(): array
    {
        $domain = (string) $this->input('dominio_corporativo');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', new CorporateEmail($domain), Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'cnpj' => [
                'required', 'string', 'size:14', Rule::unique('companies', 'cnpj'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! CnpjValidator::isValid((string) $value)) {
                        $fail('O CNPJ informado não é válido. CPF não é aceito.');
                    }
                },
            ],
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'razao_social' => ['required', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:10'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'size:2'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'dominio_corporativo' => ['required', 'string', 'max:253'],
            'email_corporativo' => ['required', 'email:rfc', 'max:255', new CorporateEmail($domain)],
            'email_recuperacao_secundario' => [
                'required', 'email:rfc', 'max:255', 'different:email', new CorporateEmail($domain),
            ],
            'company_id' => ['prohibited'],
            'is_master_admin' => ['prohibited'],
            'is_active' => ['prohibited'],
            'status' => ['prohibited'],
            'email_administrador' => ['prohibited'],
            'nome_administrador' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.size' => 'Informe os 14 dígitos de um CNPJ. CPF não é aceito.',
            'cnpj.unique' => 'Este CNPJ já possui um pré-cadastro.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'email_recuperacao_secundario.different' => 'O segundo e-mail de recuperação deve ser diferente do administrador.',
            'estado.size' => 'A UF deve ter exatamente duas letras.',
        ];
    }
}
