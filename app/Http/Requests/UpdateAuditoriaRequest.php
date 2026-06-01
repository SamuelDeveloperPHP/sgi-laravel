<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Regras idênticas a StoreAuditoriaRequest. Mantidas duplicadas
        // por clareza (cada request é independente; se evoluírem, podem
        // divergir sem afetar a outra).
        return [
            'localidade'           => 'required|string|max:255',
            'setor'                => 'required|string|max:255',
            'dataRealizacao'       => 'required|date',
            'horario_inicio'       => 'required',
            'horario_termino'      => 'required',
            'norma'                => 'required|string|max:255',
            'requisitos'           => 'required|string',
            'escopo'               => 'nullable|string',
            'auditorlider'         => 'required|string|max:255',
            'equipeAuditora'       => 'nullable|string',
            'areas_processo'       => 'nullable|string',
            'auditado'             => 'nullable|string',
            'doc_avaliados'        => 'nullable|string',
            'adms_sit_id'          => 'required|integer',
            'relatorio'            => 'required|string',
            'qtde_NC_encontradas'  => 'nullable|integer',
            'evidenciaobjetiva'    => 'nullable|string',
            'conclusoes'           => 'nullable|string',
        ];
    }
}
