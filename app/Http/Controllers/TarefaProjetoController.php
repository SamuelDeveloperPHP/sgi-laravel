<?php

namespace App\Http\Controllers;

use App\Models\TarefaProjeto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TarefaProjetoController extends Controller
{
    public function __construct()
    {
        // Wire Policy para actions RESTful (store/update/destroy registradas em rotas).
        // O método reorder() não é resource-action — é protegido pela validação
        // tenant-scoped (Rule::exists scopeada) que já filtra IDs cross-tenant.
        $this->authorizeResource(TarefaProjeto::class, 'tarefa');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Tenant-scoped: aceita projeto_id apenas se o projeto pertence
            // ao tenant atual (ou se o solicitante é master admin).
            'projeto_id' => ['required', Rule::exists('sts_projetos', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'nome' => 'required|string|max:255',
            'kanban_coluna_id' => ['nullable', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
        ]);

        $maxOrdem = TarefaProjeto::where('projeto_id', $validated['projeto_id'])
                        ->when($validated['kanban_coluna_id'] ?? null, function ($query, $colunaId) {
                            return $query->where('kanban_coluna_id', $colunaId);
                        })
                        ->max('ordem') ?? 0;
        
        $validated['ordem'] = $maxOrdem + 1;
        $validated['status'] = 'pending';

        TarefaProjeto::create($validated);

        return back()->with('message', 'Tarefa adicionada!');
    }

    public function update(Request $request, TarefaProjeto $tarefa)
    {
        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|max:45',
            'progresso' => 'sometimes|required|integer',
            'kanban_coluna_id' => ['sometimes', 'nullable', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
        ]);

        $tarefa->update($validated);

        return back()->with('message', 'Tarefa atualizada!');
    }

    public function destroy(TarefaProjeto $tarefa)
    {
        $tarefa->delete();
        return back()->with('message', 'Tarefa excluída!');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            // Tenant-scoped: cada tarefa só passa se seu company_id casa
            // com o tenant do solicitante. O TenantScope no model já filtra
            // o UPDATE subsequente, mas filtramos aqui também para retornar
            // 422 ao invés de silenciar updates inócuos.
            'tasks.*.id' => ['required', Rule::exists('sts_tarefas_projeto', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'tasks.*.kanban_coluna_id' => ['nullable', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'tasks.*.ordem' => 'required|integer',
        ]);

        foreach ($validated['tasks'] as $t) {
            TarefaProjeto::where('id', $t['id'])->update([
                'kanban_coluna_id' => $t['kanban_coluna_id'] ?? null,
                'ordem' => $t['ordem']
            ]);
        }

        return response()->json(['success' => true]);
    }
}
