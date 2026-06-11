<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAtaReuniaoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-atas-reuniao');
    }

    public function rules()
    {
        return [
            'data' => 'required|date',
            'hora_inicio' => 'required',
            'hora_termino' => 'required',
            'local' => 'required|string|max:255',
            'assunto' => 'required|string|max:255',
            'pautas' => 'required|string',
            'registro' => 'nullable|string',
            'participantes' => 'array',
            'participantes.*' => 'exists:users,id',
        ];
    }
}
