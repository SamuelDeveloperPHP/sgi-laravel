<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyAuditoriaRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Authorize is handled by policy in controller
    }

    public function rules()
    {
        return [];
    }
}
