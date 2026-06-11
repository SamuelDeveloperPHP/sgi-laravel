<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanoAcaoRequest;
use App\Http\Requests\UpdatePlanoAcaoRequest;
use App\Http\Requests\DestroyPlanoAcaoRequest;
use App\Models\PlanoAcao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        DB::beginTransaction();
        try {
            PlanoAcao::create($request->validated());

            Log::info("Ação criar plano de ação realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
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
        DB::beginTransaction();
        try {
            $planoAcao = PlanoAcao::findOrFail($id);
            $planoAcao->update($request->validated());

            Log::info("Ação atualizar plano de ação realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyPlanoAcaoRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $planoAcao = PlanoAcao::findOrFail($id);
            $planoAcao->delete();
            
            Log::info("Ação excluir plano de ação realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('planos-acao.index')->with('message', 'Plano de Ação excluído!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }
}
