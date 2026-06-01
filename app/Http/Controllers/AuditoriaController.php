<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaInterna;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditoriaController extends Controller
{
    public function __construct()
    {
        // Wire automatic Policy checks (AuditoriaInternaPolicy@viewAny/view/create/update/delete)
        // para todas as actions RESTful. Segunda camada além do TenantScope.
        $this->authorizeResource(AuditoriaInterna::class, 'auditoria');
    }

    public function index()
    {
        $auditorias = AuditoriaInterna::orderBy('created', 'desc')->paginate(10);
        return Inertia::render('Auditorias/Index', [
            'auditorias' => $auditorias
        ]);
    }

    public function create()
    {
        return Inertia::render('Auditorias/Form', [
            'auditoria' => new AuditoriaInterna(),
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'localidade' => 'required|string|max:255',
            'setor' => 'required|string|max:255',
            'dataRealizacao' => 'required|date',
            'horario_inicio' => 'required',
            'horario_termino' => 'required',
            'norma' => 'required|string|max:255',
            'requisitos' => 'required|string',
            'escopo' => 'nullable|string',
            'auditorlider' => 'required|string|max:255',
            'equipeAuditora' => 'nullable|string',
            'areas_processo' => 'nullable|string',
            'auditado' => 'nullable|string',
            'doc_avaliados' => 'nullable|string',
            'adms_sit_id' => 'required|integer',
            'relatorio' => 'required|string',
            'qtde_NC_encontradas' => 'nullable|integer',
            'evidenciaobjetiva' => 'nullable|string',
            'conclusoes' => 'nullable|string',
        ]);

        AuditoriaInterna::create($validated);

        return redirect()->route('auditorias.index')->with('message', 'Auditoria cadastrada com sucesso!');
    }

    public function show(AuditoriaInterna $auditoria)
    {
        return Inertia::render('Auditorias/Show', [
            'auditoria' => $auditoria
        ]);
    }

    public function edit(AuditoriaInterna $auditoria)
    {
        return Inertia::render('Auditorias/Form', [
            'auditoria' => $auditoria,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, AuditoriaInterna $auditoria)
    {
        $validated = $request->validate([
            'localidade' => 'required|string|max:255',
            'setor' => 'required|string|max:255',
            'dataRealizacao' => 'required|date',
            'horario_inicio' => 'required',
            'horario_termino' => 'required',
            'norma' => 'required|string|max:255',
            'requisitos' => 'required|string',
            'escopo' => 'nullable|string',
            'auditorlider' => 'required|string|max:255',
            'equipeAuditora' => 'nullable|string',
            'areas_processo' => 'nullable|string',
            'auditado' => 'nullable|string',
            'doc_avaliados' => 'nullable|string',
            'adms_sit_id' => 'required|integer',
            'relatorio' => 'required|string',
            'qtde_NC_encontradas' => 'nullable|integer',
            'evidenciaobjetiva' => 'nullable|string',
            'conclusoes' => 'nullable|string',
        ]);

        $auditoria->update($validated);

        return redirect()->route('auditorias.index')->with('message', 'Auditoria atualizada com sucesso!');
    }

    public function destroy(AuditoriaInterna $auditoria)
    {
        $auditoria->delete();
        return redirect()->route('auditorias.index')->with('message', 'Auditoria excluída com sucesso!');
    }
}
