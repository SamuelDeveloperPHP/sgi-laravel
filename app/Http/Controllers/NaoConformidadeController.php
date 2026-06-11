<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNaoConformidadeRequest;
use App\Http\Requests\UpdateNaoConformidadeRequest;
use App\Models\NaoConformidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\DestroyNaoConformidadeRequest;

class NaoConformidadeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', NaoConformidade::class);
        $query = NaoConformidade::query();

        if ($request->filled('search')) {
            $search = $request->search;
            // CORREÇÃO DE VAZAMENTO: o OR precisa estar agrupado em closure,
            // senão a precedência AND > OR faz o orWhere ESCAPAR do filtro do
            // TenantScope e retornar dados de TODAS as empresas.
            // Antes (vulnerável):  WHERE company_id = X AND id LIKE … OR setor LIKE … OR empresa LIKE …
            // Que vira:            WHERE (company_id = X AND id LIKE …) OR setor LIKE … OR empresa LIKE …
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('setorAbertura', 'like', "%{$search}%")
                  ->orWhere('empresaAbertura', 'like', "%{$search}%");
            });
        }

        $ncs = $query->orderBy('created', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('NaoConformidades/Index', [
            'ncs' => $ncs,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        $this->authorize('create', NaoConformidade::class);
        return Inertia::render('NaoConformidades/Form', [
            'nc' => new NaoConformidade(),
            'isEdit' => false
        ]);
    }

    public function store(StoreNaoConformidadeRequest $request)
    {
        $this->authorize('create', NaoConformidade::class);
        
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            if (isset($validated['evidencias']) && is_array($validated['evidencias'])) {
                $companyId = auth()->user()->company_id ?? 'sem-tenant';
                foreach ($validated['evidencias'] as $index => $evidencia) {
                    if (isset($evidencia['foto']) && $request->hasFile("evidencias.{$index}.foto")) {
                        $path = $request->file("evidencias.{$index}.foto")
                            ->store("ncs/{$companyId}", 'public');
                        $validated['evidencias'][$index]['foto'] = $path;
                    }
                }
            }

            $validated['dataAbertura'] = now();
            $validated['user_create'] = auth()->id();

            NaoConformidade::create($validated);

            Log::info("Ação Store Nao Conformidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('nao-conformidades.index')->with('message', 'Relatório de Não Conformidade criado com sucesso!');
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
        $nc = NaoConformidade::findOrFail($id);
        $this->authorize('view', $nc);
        return Inertia::render('NaoConformidades/Show', [
            'nc' => $nc
        ]);
    }

    public function edit($id)
    {
        $nc = NaoConformidade::findOrFail($id);
        $this->authorize('update', $nc);
        return Inertia::render('NaoConformidades/Form', [
            'nc' => $nc,
            'isEdit' => true
        ]);
    }

    public function update(UpdateNaoConformidadeRequest $request, $id)
    {
        $nc = NaoConformidade::findOrFail($id);
        $this->authorize('update', $nc);
        
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            if (isset($validated['evidencias']) && is_array($validated['evidencias'])) {
                $companyId = auth()->user()->company_id ?? 'sem-tenant';
                foreach ($validated['evidencias'] as $index => $evidencia) {
                    if (isset($evidencia['foto']) && $request->hasFile("evidencias.{$index}.foto")) {
                        $path = $request->file("evidencias.{$index}.foto")
                            ->store("ncs/{$companyId}", 'public');
                        $validated['evidencias'][$index]['foto'] = $path;
                    }
                }
            }

            $validated['user_edit'] = auth()->id();
            $nc->update($validated);

            Log::info("Ação Update Nao Conformidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('nao-conformidades.index')->with('message', 'Relatório de Não Conformidade atualizado!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyNaoConformidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $nc = NaoConformidade::findOrFail($id);
            $nc->delete();
            
            Log::info("Ação Destroy Nao Conformidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('nao-conformidades.index')->with('message', 'Não Conformidade excluída com sucesso!');
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
