<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKanbanColunaRequest;
use App\Http\Requests\UpdateKanbanColunaRequest;
use App\Models\KanbanColuna;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KanbanColunaController extends Controller
{
    public function __construct()
    {
        // Wire Policy. reorder() não é resource-action, é protegido por validação.
        $this->authorizeResource(KanbanColuna::class, 'kanban_coluna');
    }

    public function store(StoreKanbanColunaRequest $request)
    {
        $validated = $request->validated();

        $count = KanbanColuna::where('projeto_id', $validated['projeto_id'])->count();
        if ($count >= 6) {
            return back()->withErrors(['message' => 'Limite máximo de 6 colunas atingido para este projeto.']);
        }

        $maxOrdem = KanbanColuna::where('projeto_id', $validated['projeto_id'])->max('ordem') ?? 0;
        $validated['ordem'] = $maxOrdem + 1;

        KanbanColuna::create($validated);

        return back()->with('message', 'Coluna criada!');
    }

    public function update(UpdateKanbanColunaRequest $request, KanbanColuna $kanban_coluna)
    {
        $kanban_coluna->update($request->validated());

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
