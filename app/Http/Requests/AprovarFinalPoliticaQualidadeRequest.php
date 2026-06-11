<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AprovarFinalPoliticaQualidadeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-politica-qualidade');
    }

    public function rules()
    {
        return [];
    }
}
