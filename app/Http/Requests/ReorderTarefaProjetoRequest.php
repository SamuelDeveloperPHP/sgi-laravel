<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTarefaProjetoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'tasks' => 'required|array',
            'tasks.*.id' => ['required', Rule::exists('sts_tarefas_projeto', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'tasks.*.kanban_coluna_id' => ['nullable', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'tasks.*.ordem' => 'required|integer',
        ];
    }
}
