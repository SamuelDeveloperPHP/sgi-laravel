<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprovarFinalMissaoVisaoValoresRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-missao-visao-valores');
    }

    public function rules()
    {
        return [];
    }
}
