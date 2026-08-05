<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateControleCalibracaoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-controle-calibracoes');
    }

    public function rules()
    {
        return [
            'equipamento' => 'required|string|max:255',
            'local' => 'nullable|string|max:255',
            'identificacao' => 'nullable|string|max:255',
            'certificado_numero' => 'nullable|string|max:255',
            'frequencia_meses' => 'nullable|integer',
            'data_ultima_calibracao' => 'nullable|date',
            'data_proxima_calibracao' => 'nullable|date',
            'observacoes' => 'nullable|string',
            'arquivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
