<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtaReuniaoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-atas-reuniao');
    }

    public function rules()
    {
        $companyId = $this->route('ata')->company_id;

        return [
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
