<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyObjetivoQualidadeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('manage-objetivos-qualidade');
    }

    public function rules()
    {
        return [];
    }
}
