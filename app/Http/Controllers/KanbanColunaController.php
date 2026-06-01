<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\KanbanColuna;

class KanbanColunaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Tenant-scoped: só aceita projeto_id se o projeto pertence
            // ao tenant atual (ou se o solicitante é master admin).
            'projeto_id' => ['required', Rule::exists('sts_projetos', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'nome' => 'required|string|max:255',
        ]);

        $count = KanbanColuna::where('projeto_id', $validated['projeto_id'])->count();
        if ($count >= 6) {
            return back()->withErrors(['message' => 'Limite máximo de 6 colunas atingido para este projeto.']);
        }

        $maxOrdem = KanbanColuna::where('projeto_id', $validated['projeto_id'])->max('ordem') ?? 0;
        $validated['ordem'] = $maxOrdem + 1;

        KanbanColuna::create($validated);

        return back()->with('message', 'Coluna criada!');
    }

    public function update(Request $request, KanbanColuna $kanban_coluna)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $kanban_coluna->update($validated);

        return back();
    }

    public function destroy(KanbanColuna $kanban_coluna)
    {
        // Ao excluir a coluna, opcional: deletar as tarefas ou deixá-las órfãs. 
        // A constraint de nullOnDelete fará o id ficar null.
        $kanban_coluna->delete();

        return back()->with('message', 'Coluna excluída!');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'columns' => 'required|array',
            // Tenant-scoped: cada coluna só passa se seu company_id casa
            // com o tenant. TenantScope no model também filtra o UPDATE.
            'columns.*.id' => ['required', Rule::exists('kanban_colunas', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'columns.*.ordem' => 'required|integer'
        ]);

        foreach ($validated['columns'] as $col) {
            KanbanColuna::where('id', $col['id'])->update(['ordem' => $col['ordem']]);
        }

        return response()->json(['success' => true]);
    }
}
