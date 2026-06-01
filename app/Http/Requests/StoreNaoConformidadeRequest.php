<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNaoConformidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dados_origem'         => 'nullable|array',
            'descOcorrencia'       => 'nullable|string',
            'acao_contencao_grid'  => 'nullable|array',
            'cinco_porques'        => 'nullable|array',
            'plano_acao_grid'      => 'nullable|array',
            'evidencias'           => 'nullable|array',
            // Validação granular do upload (cada evidência pode trazer arquivo):
            'evidencias.*.foto'    => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ];
    }
}
