<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjetivoQualidadeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-objetivos-qualidade');
    }

    public function rules()
    {
        return [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'prazo' => 'required|date',
            'responsaveis' => 'required|array',
            'responsaveis.*' => 'exists:users,id',
            'revisor_id' => 'nullable|exists:users,id',
            'aprovador_id' => 'nullable|exists:users,id',
        ];
    }
}
