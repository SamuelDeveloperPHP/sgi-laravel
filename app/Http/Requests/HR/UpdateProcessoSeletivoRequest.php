<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessoSeletivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:Em Andamento,Concluído,Cancelado'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'custo_planejado' => ['required', 'numeric', 'min:0'],
            'custo_realizado' => ['required', 'numeric', 'min:0'],
        ];
    }
}
