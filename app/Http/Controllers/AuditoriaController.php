<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditoriaRequest;
use App\Http\Requests\UpdateAuditoriaRequest;
use App\Models\AuditoriaInterna;
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

    public function store(StoreAuditoriaRequest $request)
    {
        AuditoriaInterna::create($request->validated());

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

    public function update(UpdateAuditoriaRequest $request, AuditoriaInterna $auditoria)
    {
        $auditoria->update($request->validated());

        return redirect()->route('auditorias.index')->with('message', 'Auditoria atualizada com sucesso!');
    }

    public function destroy(AuditoriaInterna $auditoria)
    {
        $auditoria->delete();
        return redirect()->route('auditorias.index')->with('message', 'Auditoria excluída com sucesso!');
    }
}
