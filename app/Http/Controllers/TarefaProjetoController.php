<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTarefaProjetoRequest;
use App\Http\Requests\UpdateTarefaProjetoRequest;
use App\Http\Requests\DestroyTarefaProjetoRequest;
use App\Http\Requests\ReorderTarefaProjetoRequest;
use App\Http\Requests\StoreTarefaProjetoComentarioRequest;
use App\Http\Requests\DestroyTarefaProjetoComentarioRequest;
use App\Http\Requests\StoreTarefaProjetoAnexoRequest;
use App\Http\Requests\DestroyTarefaProjetoAnexoRequest;
use App\Http\Requests\StoreTarefaProjetoChecklistRequest;
use App\Http\Requests\UpdateTarefaProjetoChecklistRequest;
use App\Http\Requests\DestroyTarefaProjetoChecklistRequest;
use App\Models\TarefaProjeto;
use App\Models\TarefaProjetoComentario;
use App\Models\TarefaProjetoAnexo;
use App\Models\TarefaProjetoChecklist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TarefaProjetoController extends Controller
{
    public function __construct()
    {
        // Wire Policy para actions RESTful (store/update/destroy registradas em rotas).
        // O método reorder() não é resource-action — é protegido pela validação
        // tenant-scoped (Rule::exists scopeada) que já filtra IDs cross-tenant.
        $this->authorizeResource(TarefaProjeto::class, 'tarefa');
    }

    public function store(StoreTarefaProjetoRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $maxOrdem = TarefaProjeto::where('projeto_id', $validated['projeto_id'])
                            ->when($validated['kanban_coluna_id'] ?? null, function ($query, $colunaId) {
                                return $query->where('kanban_coluna_id', $colunaId);
                            })
                            ->max('ordem') ?? 0;
            
            $validated['ordem'] = $maxOrdem + 1;
            $validated['status'] = 'pending';

            $tarefa = TarefaProjeto::create($validated);

            if (isset($validated['users'])) {
                $tarefa->users()->sync($validated['users']);
            }

            Log::info("Ação criar tarefa projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return back()->with('message', 'Tarefa adicionada!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function update(UpdateTarefaProjetoRequest $request, TarefaProjeto $tarefa)
    {
        DB::beginTransaction();
        try {
            $tarefa->update($request->validated());

            if ($request->has('users')) {
                $tarefa->users()->sync($request->validated('users'));
            }

            Log::info("Ação atualizar tarefa projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return back()->with('message', 'Tarefa atualizada!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    /**
     * Atualização leve para o Gantt: reagendar (arrastar/redimensionar) e progresso.
     * Recalcula tempo_duracao como a diferença entre as datas (em dias).
     * Retorna JSON — chamado via axios, sem recarregar a página.
     */
    public function updateGantt(Request $request, TarefaProjeto $tarefa)
    {
        $this->authorize('update', $tarefa);

        $request->validate([
            'dt_inicio' => ['nullable', 'date_format:Y-m-d'],
            'dt_fim'    => ['nullable', 'date_format:Y-m-d'],
            'progresso' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $payload = array_filter(
            $request->only(['dt_inicio', 'dt_fim', 'progresso']),
            fn ($v) => $v !== null
        );

        $ini = $payload['dt_inicio'] ?? $tarefa->dt_inicio;
        $fim = $payload['dt_fim'] ?? $tarefa->dt_fim;
        if ($ini && $fim) {
            $payload['tempo_duracao'] = (int) round(abs(
                \Carbon\Carbon::parse($ini)->diffInDays(\Carbon\Carbon::parse($fim))
            ));
        }

        if ($payload) {
            $tarefa->update($payload);
        }

        return response()->json([
            'success'       => true,
            'id'            => $tarefa->id,
            'dt_inicio'     => $tarefa->dt_inicio,
            'dt_fim'        => $tarefa->dt_fim,
            'tempo_duracao' => $tarefa->tempo_duracao,
            'progresso'     => $tarefa->progresso,
        ]);
    }

    public function destroy(DestroyTarefaProjetoRequest $request, TarefaProjeto $tarefa)
    {
        DB::beginTransaction();
        try {
            $tarefa->delete();
            Log::info("Ação excluir tarefa projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('message', 'Tarefa excluída!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function reorder(ReorderTarefaProjetoRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            foreach ($validated['tasks'] as $t) {
                TarefaProjeto::where('id', $t['id'])->update([
                    'kanban_coluna_id' => $t['kanban_coluna_id'] ?? null,
                    'ordem' => $t['ordem']
                ]);
            }

            Log::info("Ação reordenar tarefa projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Erro interno ao realizar operação.'], 500);
        }
    }

    public function storeComment(StoreTarefaProjetoComentarioRequest $request, TarefaProjeto $tarefa)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $tarefa->comentarios()->create([
                'user_id' => auth()->id(),
                'mensagem' => $validated['mensagem']
            ]);

            Log::info("Ação adicionar comentário em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function deleteComment(DestroyTarefaProjetoComentarioRequest $request, TarefaProjetoComentario $comentario)
    {
        DB::beginTransaction();
        try {
            $comentario->delete();
            Log::info("Ação excluir comentário em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function storeAttachment(StoreTarefaProjetoAnexoRequest $request, TarefaProjeto $tarefa)
    {
        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $path = $file->store(
                'companies/' . $tarefa->company_id . '/tarefas/anexos',
                'local'
            );

            $tarefa->anexos()->create([
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
            ]);

            Log::info("Ação adicionar anexo em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function deleteAttachment(DestroyTarefaProjetoAnexoRequest $request, TarefaProjetoAnexo $anexo)
    {
        DB::beginTransaction();
        try {
            Storage::disk('local')->delete($anexo->file_path);
            $anexo->delete();
            Log::info("Ação excluir anexo em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function downloadAttachment(TarefaProjetoAnexo $anexo)
    {
        $this->authorize('view', $anexo->tarefa);

        if (!Storage::disk('local')->exists($anexo->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($anexo->file_path, $anexo->file_name);
    }

    public function storeChecklist(StoreTarefaProjetoChecklistRequest $request, TarefaProjeto $tarefa)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $maxOrdem = $tarefa->checklists()->max('ordem') ?? 0;

            $tarefa->checklists()->create([
                'descricao' => $validated['descricao'],
                'ordem' => $maxOrdem + 1,
                'concluido' => false
            ]);

            Log::info("Ação adicionar checklist em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function updateChecklist(UpdateTarefaProjetoChecklistRequest $request, TarefaProjetoChecklist $checklist)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $checklist->update($validated);
            Log::info("Ação atualizar checklist em tarefa realizada pelo usuário " . auth()->user()->id);
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

    public function deleteChecklist(DestroyTarefaProjetoChecklistRequest $request, TarefaProjetoChecklist $checklist)
    {
        DB::beginTransaction();
        try {
            $checklist->delete();
            Log::info("Ação excluir checklist em tarefa realizada pelo usuário " . auth()->user()->id);
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
}
