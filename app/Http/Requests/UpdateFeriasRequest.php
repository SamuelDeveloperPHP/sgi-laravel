<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\TenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeriasRequest extends FormRequest
{
    use TenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'funcionario_id' => ['required', $this->tenantScopedExists('rh_funcionarios')],
            'periodo_aquisitivo_inicio' => 'nullable|date',
            'periodo_aquisitivo_fim' => 'nullable|date|after_or_equal:periodo_aquisitivo_inicio',
            'dias_direito' => 'nullable|integer|min:0|max:30',
            'opcao_abono' => 'boolean',
            'dias_abono' => 'nullable|integer|min:0|max:10',
            
            'gozo_1_inicio' => 'nullable|date',
            'gozo_1_fim' => 'nullable|date|after_or_equal:gozo_1_inicio',
            
            'gozo_2_inicio' => 'nullable|date|after:gozo_1_fim',
            'gozo_2_fim' => 'nullable|date|after_or_equal:gozo_2_inicio',
            
            'gozo_3_inicio' => 'nullable|date|after:gozo_2_fim',
            'gozo_3_fim' => 'nullable|date|after_or_equal:gozo_3_inicio',
            
            'faltas' => 'nullable|integer|min:0',
            'valor_proventos' => 'nullable|numeric|min:0',
            'valor_1_3' => 'nullable|numeric|min:0',
            'valor_1_3_abono' => 'nullable|numeric|min:0',
            'desconto_inss' => 'nullable|numeric|min:0',
            'desconto_irpf' => 'nullable|numeric|min:0',
            'valor_liquido' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Programada,Em Gozo,Concluída,Cancelada',
        ];
    }
}
