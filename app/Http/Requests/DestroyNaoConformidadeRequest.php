<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\NaoConformidade;

class DestroyNaoConformidadeRequest extends FormRequest
{
    public function authorize()
    {
        $id = $this->route('id') ?? $this->route('nao_conformidade');
        $nc = NaoConformidade::findOrFail($id);
        return auth()->check() && auth()->user()->can('delete', $nc);
    }

    public function rules()
    {
        return [];
    }
}
