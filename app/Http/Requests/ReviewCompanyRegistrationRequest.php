<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCompanyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_master_admin === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $required = $this->routeIs('admin.company-registrations.reject') ? 'required' : 'nullable';

        return [
            'reason' => [$required, 'string', 'max:2000'],
            'status' => ['prohibited'],
            'company_id' => ['prohibited'],
            'registration_status' => ['prohibited'],
            'registration_reviewed_by' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Informe a justificativa da rejeição.',
            'reason.max' => 'A justificativa deve ter no máximo 2.000 caracteres.',
        ];
    }
}
