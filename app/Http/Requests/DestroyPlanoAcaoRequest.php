<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PlanoAcao;

class DestroyPlanoAcaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planoAcao = PlanoAcao::findOrFail($this->route('id'));
        return auth()->check() && auth()->user()->can('delete', $planoAcao);
    }

    public function rules(): array
    {
        return [];
    }
}
