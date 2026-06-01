<?php

namespace App\Http\Controllers;

use App\Models\PlanoAcao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanoAcaoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', PlanoAcao::class);
        $planos = PlanoAcao::orderBy('created', 'desc')->paginate(10);
        
        return Inertia::render('PlanosAcao/Index', [
            'planos' => $planos
        ]);
    }

    public function create()
    {
        $this->authorize('create', PlanoAcao::class);
        return Inertia::render('PlanosAcao/Form', [
            'plano' => new PlanoAcao(),
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', PlanoAcao::class);
        $validated = $request->validate([
            'adms_sit_id' => 'required|integer',
            'adms_usuario_id' => 'required|integer',
            'data_cad' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'o_q_aconteceu' => 'required|string',
            'responsaveis' => 'required|string|max:255',
            'dt_prazo' => 'required|date',
            'onde_ocorreu' => 'nullable|string',
            'porque_ocorreu' => 'nullable|string',
            'como_resolver' => 'nullable|string',
            'custo' => 'nullable|numeric',
            'data_concluido' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        PlanoAcao::create($validated);

        return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação criado com sucesso!');
    }

    public function show($id)
    {
        $planoAcao = PlanoAcao::findOrFail($id);
        $this->authorize('view', $planoAcao);
        return Inertia::render('PlanosAcao/Show', [
            'plano' => $planoAcao
        ]);
    }

    public function edit($id)
    {
        $planoAcao = PlanoAcao::findOrFail($id);
        $this->authorize('update', $planoAcao);
        return Inertia::render('PlanosAcao/Form', [
            'plano' => $planoAcao,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        $planoAcao = PlanoAcao::findOrFail($id);
        $this->authorize('update', $planoAcao);

        $validated = $request->validate([
            'adms_sit_id' => 'required|integer',
            'adms_usuario_id' => 'required|integer',
            'data_cad' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'o_q_aconteceu' => 'required|string',
            'responsaveis' => 'required|string|max:255',
            'dt_prazo' => 'required|date',
            'onde_ocorreu' => 'nullable|string',
            'porque_ocorreu' => 'nullable|string',
            'como_resolver' => 'nullable|string',
            'custo' => 'nullable|numeric',
            'data_concluido' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        $planoAcao->update($validated);

        return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $planoAcao = PlanoAcao::findOrFail($id);
        $this->authorize('delete', $planoAcao);
        $planoAcao->delete();
        
        return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação excluído!');
    }
}
