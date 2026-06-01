<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjetoController extends Controller
{
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomeProjeto' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
            'porc_concluido' => 'nullable|integer|min:0|max:100',
            'ativo' => 'nullable|boolean',
            'privacidade' => 'nullable|string',
            'tags' => 'nullable|array',
            // Tenant-scoped: só aceita usuários cujo users.company_id casa
            // com o tenant do solicitante (master admin bypassa o filtro).
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'membros' => 'nullable|array',
            'membros.*' => [Rule::exists('users', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'imagem_capa' => 'nullable|image|max:2048',
            'arquivos_anexos' => 'nullable|array',
            'arquivos_anexos.*' => 'nullable|file|max:10240'
        ]);

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

        return redirect()->route('projetos.index')->with('message', 'Projeto criado com sucesso!');
    }

    public function show(Projeto $projeto)
    {
        $projeto->load(['kanbanColunas.tarefas', 'tarefas']);

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

    public function update(Request $request, Projeto $projeto)
    {
        $validated = $request->validate([
            'nomeProjeto' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
            'porc_concluido' => 'nullable|integer|min:0|max:100',
            'ativo' => 'nullable|boolean',
            'privacidade' => 'nullable|string',
            'tags' => 'nullable|array',
            // Tenant-scoped: só aceita usuários cujo users.company_id casa
            // com o tenant do solicitante (master admin bypassa o filtro).
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'membros' => 'nullable|array',
            'membros.*' => [Rule::exists('users', 'id')->where(function ($q) {
                if (!auth()->user()->is_master_admin) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })],
            'imagem_capa' => 'nullable|image|max:2048',
            'arquivos_anexos' => 'nullable|array',
            'arquivos_anexos.*' => 'nullable|file|max:10240'
        ]);

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

        return redirect()->route('projetos.index')->with('message', 'Projeto atualizado com sucesso!');
    }

    public function destroy(Projeto $projeto)
    {
        $projeto->delete();
        return redirect()->route('projetos.index')->with('message', 'Projeto excluído!');
    }
}
