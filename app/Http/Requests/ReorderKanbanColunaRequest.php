<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderKanbanColunaRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'columns' => 'required|array',
            'columns.*.id' => ['required', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'columns.*.ordem' => 'required|integer'
        ];
    }
}
