<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTreinamentoRequest extends FormRequest
{
    use TenantScopedRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'curso_id' => ['required', $this->tenantScopedExists('cursos')],
            'local_treinamento_id' => ['nullable', $this->tenantScopedExists('local_treinamentos')],
            'instrutor' => 'nullable|string|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'status' => 'required|string|in:Agendado,Em Andamento,Concluído,Cancelado',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }
}
