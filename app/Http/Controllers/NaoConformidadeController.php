<?php

namespace App\Http\Controllers;

use App\Models\NaoConformidade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class NaoConformidadeController extends Controller
{
    public function index(Request $request)
    {
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
        return Inertia::render('NaoConformidades/Form', [
            'nc' => new NaoConformidade(),
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dados_origem' => 'nullable|array',
            'descOcorrencia' => 'nullable|string',
            'acao_contencao_grid' => 'nullable|array',
            'cinco_porques' => 'nullable|array',
            'plano_acao_grid' => 'nullable|array',
            'evidencias' => 'nullable|array',
        ]);

        if (isset($validated['evidencias']) && is_array($validated['evidencias'])) {
            // Namespace storage por tenant: 'ncs/<company_id>/<hash>.ext'.
            // Fase 1 mantém o disco 'public' (frontend continua usando
            // /storage/...) mas introduz boundary tenant na URL.
            // TODO Fase 2: migrar para disco privado + signed URLs com
            // checagem de tenant no momento do download (impede enumeração
            // direta de URL pública).
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

        return redirect()->route('nao-conformidades.index')->with('message', 'Relatório de Não Conformidade criado com sucesso!');
    }

    public function show($id)
    {
        $nc = NaoConformidade::findOrFail($id);
        return Inertia::render('NaoConformidades/Show', [
            'nc' => $nc
        ]);
    }

    public function edit($id)
    {
        $nc = NaoConformidade::findOrFail($id);
        return Inertia::render('NaoConformidades/Form', [
            'nc' => $nc,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        $nc = NaoConformidade::findOrFail($id);

        $validated = $request->validate([
            'dados_origem' => 'nullable|array',
            'descOcorrencia' => 'nullable|string',
            'acao_contencao_grid' => 'nullable|array',
            'cinco_porques' => 'nullable|array',
            'plano_acao_grid' => 'nullable|array',
            'evidencias' => 'nullable|array',
        ]);

        if (isset($validated['evidencias']) && is_array($validated['evidencias'])) {
            // Namespace storage por tenant: 'ncs/<company_id>/<hash>.ext'.
            // Fase 1 mantém o disco 'public' (frontend continua usando
            // /storage/...) mas introduz boundary tenant na URL.
            // TODO Fase 2: migrar para disco privado + signed URLs com
            // checagem de tenant no momento do download (impede enumeração
            // direta de URL pública).
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

        return redirect()->route('nao-conformidades.index')->with('message', 'Relatório de Não Conformidade atualizado!');
    }

    public function destroy($id)
    {
        $nc = NaoConformidade::findOrFail($id);
        $nc->delete();
        return redirect()->route('nao-conformidades.index')->with('message', 'Não Conformidade excluída com sucesso!');
    }
}
