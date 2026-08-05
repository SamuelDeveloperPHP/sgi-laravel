<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\ProcessoSeletivo;
use App\Http\Requests\HR\StoreProcessoSeletivoRequest;
use App\Http\Requests\HR\UpdateProcessoSeletivoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessoSeletivoController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcessoSeletivo::query();

        if ($request->filled('search')) {
            $query->where('nome', 'like', '%' . $request->search . '%');
        }

        $processos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('HR/ProcessosSeletivos/Index', [
            'processos' => $processos,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(StoreProcessoSeletivoRequest $request)
    {
        ProcessoSeletivo::create($request->validated());
        return redirect()->back()->with('success', 'Processo Seletivo criado com sucesso!');
    }

    public function show(ProcessoSeletivo $processoSeletivo)
    {
        $processoSeletivo->load('candidatos');
        
        return Inertia::render('HR/ProcessosSeletivos/Show', [
            'processo' => $processoSeletivo
        ]);
    }

    public function update(UpdateProcessoSeletivoRequest $request, ProcessoSeletivo $processoSeletivo)
    {
        $processoSeletivo->update($request->validated());
        return redirect()->back()->with('success', 'Processo Seletivo atualizado com sucesso!');
    }

    public function destroy(ProcessoSeletivo $processoSeletivo)
    {
        $processoSeletivo->delete();
        return redirect()->back()->with('success', 'Processo Seletivo excluído com sucesso!');
    }
}
