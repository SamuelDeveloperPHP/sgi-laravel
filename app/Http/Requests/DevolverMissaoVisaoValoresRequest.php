<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevolverMissaoVisaoValoresRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-missao-visao-valores');
    }

    public function rules()
    {
        return [];
    }
}
