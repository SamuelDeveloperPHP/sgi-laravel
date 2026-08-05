<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyDocumentoRegistroRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-controle-documentos');
    }

    public function rules()
    {
        return [];
    }
}
