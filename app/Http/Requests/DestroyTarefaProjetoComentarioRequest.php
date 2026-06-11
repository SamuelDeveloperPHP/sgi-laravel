<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyTarefaProjetoComentarioRequest extends FormRequest
{
    public function authorize(): bool {
        $comentario = $this->route('comentario');
        return $comentario && ($comentario->user_id === auth()->id() || auth()->user()->is_master_admin);
    }
    public function rules(): array { return []; }
}
