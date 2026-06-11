<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevolverEscopoRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage-escopo');
    }

    public function rules()
    {
        return [];
    }
}
