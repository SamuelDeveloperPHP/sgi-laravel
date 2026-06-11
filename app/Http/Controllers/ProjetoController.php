<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjetoRequest;
use App\Http\Requests\UpdateProjetoRequest;
use App\Http\Requests\DestroyProjetoRequest;
use App\Models\Projeto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjetoController extends Controller
{
    public function __construct()
    {
        // Wire automatic Policy checks (ProjetoPolicy@viewAny/view/create/update/delete).
        $this->authorizeResource(Projeto::class, 'projeto');
    }

    public function index()
    {
        $projetos = Projeto::with(['membros', 'responsavel'])->orderBy('ordem')->get();
        return Inertia::render('Projetos/Index', [
            'projetos' => $projetos
        ]);
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        return Inertia::render('Projetos/Form', [
            'projeto' => new Projeto(),
            'users' => $users
        ]);
    }

    public function store(StoreProjetoRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            if (isset($validated['tags'])) {
                $validated['tags'] = json_encode($validated['tags']);
            }

            if ($request->hasFile('imagem_capa')) {
                $validated['imagem_capa'] = $request->file('imagem_capa')->store('projetos/capas', 'public');
            }

            if ($request->hasFile('arquivos_anexos')) {
                $paths = [];
                foreach ($request->file('arquivos_anexos') as $file) {
                    $paths[] = $file->store('projetos/anexos', 'public');
                }
                $validated['arquivos_anexos'] = $paths;
            }

            $projeto = Projeto::create($validated);

            if (isset($validated['membros'])) {
                // Defesa em profundidade: refiltra IDs antes do sync, caso a
                // regra de validação seja relaxada no futuro. Master admin
                // bypassa para suportar gestão cross-tenant.
                $tenantId = auth()->user()->company_id;
                $validMembros = auth()->user()->is_master_admin
                    ? $validated['membros']
                    : User::whereIn('id', $validated['membros'])
                        ->where('company_id', $tenantId)
                        ->pluck('id')
                        ->all();
                $projeto->membros()->sync($validMembros);
            }

            Log::info("Ação criar projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('projetos.index')->with('message', 'Projeto criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show(Projeto $projeto)
    {
        $projeto->load([
            'kanbanColunas.tarefas.comentarios.user', 
            'kanbanColunas.tarefas.anexos', 
            'kanbanColunas.tarefas.checklists', 
            'tarefas'
        ]);

        if ($projeto->kanbanColunas->isEmpty()) {
            $col1 = $projeto->kanbanColunas()->create(['nome' => 'A Fazer', 'ordem' => 1]);
            $projeto->kanbanColunas()->create(['nome' => 'Em Andamento', 'ordem' => 2]);
            $projeto->kanbanColunas()->create(['nome' => 'Concluído', 'ordem' => 3]);
            
            // Atribui tarefas órfãs à primeira coluna
            $projeto->tarefas()->whereNull('kanban_coluna_id')->update(['kanban_coluna_id' => $col1->id]);
            
            $projeto->load('kanbanColunas.tarefas'); // Reload
        }

        return Inertia::render('Projetos/Show', [
            'projeto' => $projeto
        ]);
    }

    public function edit(Projeto $projeto)
    {
        $projeto->load('membros');
        // Decode tags if needed
        if (is_string($projeto->tags)) {
            $projeto->tags = json_decode($projeto->tags, true);
        }

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        return Inertia::render('Projetos/Form', [
            'projeto' => $projeto,
            'users' => $users
        ]);
    }

    public function update(UpdateProjetoRequest $request, Projeto $projeto)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();

            if (isset($validated['tags'])) {
                $validated['tags'] = json_encode($validated['tags']);
            }

            if ($request->hasFile('imagem_capa')) {
                $validated['imagem_capa'] = $request->file('imagem_capa')->store('projetos/capas', 'public');
            }

            if ($request->hasFile('arquivos_anexos')) {
                $paths = is_array($projeto->arquivos_anexos) ? $projeto->arquivos_anexos : [];
                foreach ($request->file('arquivos_anexos') as $file) {
                    $paths[] = $file->store('projetos/anexos', 'public');
                }
                $validated['arquivos_anexos'] = $paths;
            }

            $projeto->update($validated);

            if (isset($validated['membros'])) {
                // Defesa em profundidade: refiltra IDs antes do sync, caso a
                // regra de validação seja relaxada no futuro. Master admin
                // bypassa para suportar gestão cross-tenant.
                $tenantId = auth()->user()->company_id;
                $validMembros = auth()->user()->is_master_admin
                    ? $validated['membros']
                    : User::whereIn('id', $validated['membros'])
                        ->where('company_id', $tenantId)
                        ->pluck('id')
                        ->all();
                $projeto->membros()->sync($validMembros);
            }

            Log::info("Ação atualizar projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('projetos.index')->with('message', 'Projeto atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyProjetoRequest $request, Projeto $projeto)
    {
        DB::beginTransaction();
        try {
            $projeto->delete();
            
            Log::info("Ação excluir projeto realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('projetos.index')->with('message', 'Projeto excluído!');
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
