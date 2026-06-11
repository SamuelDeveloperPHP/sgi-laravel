<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanoAcaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planoAcao = \App\Models\PlanoAcao::findOrFail($this->route('id'));
        return auth()->check() && auth()->user()->can('update', $planoAcao);
    }

    public function rules(): array
    {
        return [
            'adms_sit_id'      => 'required|integer',
            'adms_usuario_id'  => 'required|integer',
            'data_cad'         => 'nullable|date',
            'status'           => 'nullable|string|max:50',
            'o_q_aconteceu'    => 'required|string',
            'responsaveis'     => 'required|string|max:255',
            'dt_prazo'         => 'required|date',
            'onde_ocorreu'     => 'nullable|string',
            'porque_ocorreu'   => 'nullable|string',
            'como_resolver'    => 'nullable|string',
            'custo'            => 'nullable|numeric',
            'data_concluido'   => 'nullable|date',
            'observacoes'      => 'nullable|string',
        ];
    }
}
