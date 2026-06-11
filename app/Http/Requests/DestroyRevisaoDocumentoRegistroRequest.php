<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRevisaoDocumentoRegistroRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-controle-documentos');
    }

    public function rules()
    {
        return [];
    }
}
