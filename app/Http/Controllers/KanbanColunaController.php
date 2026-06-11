<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKanbanColunaRequest;
use App\Http\Requests\UpdateKanbanColunaRequest;
use App\Models\KanbanColuna;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ReorderKanbanColunaRequest;

class KanbanColunaController extends Controller
{
    public function __construct()
    {
        // Wire Policy. reorder() não é resource-action, é protegido por validação.
        $this->authorizeResource(KanbanColuna::class, 'kanban_coluna');
    }

    public function store(StoreKanbanColunaRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $count = KanbanColuna::where('projeto_id', $validated['projeto_id'])->count();
            if ($count >= 6) {
                return back()->withErrors(['message' => 'Limite máximo de 6 colunas atingido para este projeto.']);
            }

            $maxOrdem = KanbanColuna::where('projeto_id', $validated['projeto_id'])->max('ordem') ?? 0;
            $validated['ordem'] = $maxOrdem + 1;

            KanbanColuna::create($validated);

            Log::info("Ação Store Kanban Coluna realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('message', 'Coluna criada!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function update(UpdateKanbanColunaRequest $request, KanbanColuna $kanban_coluna)
    {
        DB::beginTransaction();
        try {
            $kanban_coluna->update($request->validated());

            Log::info("Ação Update Kanban Coluna realizada pelo usuário " . auth()->id());
            DB::commit();
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(KanbanColuna $kanban_coluna)
    {
        DB::beginTransaction();
        try {
            // Ao excluir a coluna, opcional: deletar as tarefas ou deixá-las órfãs. 
            // A constraint de nullOnDelete fará o id ficar null.
            $kanban_coluna->delete();

            Log::info("Ação Destroy Kanban Coluna realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('message', 'Coluna excluída!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function reorder(ReorderKanbanColunaRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            foreach ($validated['columns'] as $col) {
                KanbanColuna::where('id', $col['id'])->update(['ordem' => $col['ordem']]);
            }

            Log::info("Ação Reorder Kanban Coluna realizada pelo usuário " . auth()->id());
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro interno ao realizar operação.'], 500);
        }
    }
}
