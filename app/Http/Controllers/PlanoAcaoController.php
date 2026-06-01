<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanoAcaoRequest;
use App\Http\Requests\UpdatePlanoAcaoRequest;
use App\Models\PlanoAcao;
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

    public function store(StorePlanoAcaoRequest $request)
    {
        $this->authorize('create', PlanoAcao::class);
        PlanoAcao::create($request->validated());

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

    public function update(UpdatePlanoAcaoRequest $request, $id)
    {
        $planoAcao = PlanoAcao::findOrFail($id);
        $this->authorize('update', $planoAcao);

        $planoAcao->update($request->validated());

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
