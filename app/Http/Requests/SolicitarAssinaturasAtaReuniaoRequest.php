<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarAssinaturasAtaReuniaoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-atas-reuniao');
    }

    public function rules()
    {
        return [];
    }
}
