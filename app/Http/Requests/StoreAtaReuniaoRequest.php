<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAtaReuniaoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->user()->is_master_admin) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-atas-reuniao');
    }

    public function rules()
    {
        $companyId = $this->user()->is_master_admin
            ? $this->input('company_id')
            : $this->user()->company_id;

        return [
            'company_id' => ['required', Rule::exists('companies', 'id')],
            'data' => 'required|date',
            'hora_inicio' => 'required',
            'hora_termino' => 'required',
            'local' => 'required|string|max:255',
            'assunto' => 'required|string|max:255',
            'pautas' => 'required|string',
            'registro' => 'nullable|string',
            'participantes' => 'array',
            'participantes.*' => [Rule::exists('users', 'id')->where('company_id', $companyId)],
        ];
    }
}
