<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyMapaRiscoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->is_master_admin || $user->can('manage-mapas-risco');
    }

    public function rules(): array
    {
        return [];
    }
}
