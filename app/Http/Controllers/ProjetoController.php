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

    /**
     * Visão de cronograma (Gantt) do projeto.
     * Rota protegida por CheckMasterAdmin; a policy reforça (master admin bypassa via before()).
     * As tarefas usam o modelo herdado do jQueryGantt (dt_inicio, tempo_duracao, dt_fim,
     * progresso, level, dependencias), consumido por uma lib Gantt nativa de React.
     */
    public function gantt(Projeto $projeto)
    {
        $this->authorize('view', $projeto);

        // Página Blade standalone com o jQueryGantt (Twproject) — abre em nova aba.
        return view('projetos.gantt', [
            'projeto' => $projeto,
            'project' => $this->buildGanttProject($projeto),
        ]);
    }

    /**
     * Converte as tarefas (sts_tarefas_projeto = modelo do jQueryGantt) para o JSON que
     * GanttMaster.loadProject() espera. Reutilizado ao abrir e ao retornar o projeto
     * atualizado após salvar (sincroniza IDs de tarefas novas -> evita duplicação).
     */
    private function buildGanttProject(Projeto $projeto): array
    {
        $tarefas = DB::table('sts_tarefas_projeto')
            ->where('projeto_id', $projeto->id)
            ->orderBy('ordem')->orderBy('id')
            ->get();

        // Linha (1-based) de cada tarefa, para converter dependências (id -> linha).
        $rowById = [];
        foreach ($tarefas as $i => $t) {
            $rowById[$t->id] = $i + 1;
        }

        // Responsáveis (pivot) -> resources/assigs do jQueryGantt.
        $pivot = DB::table('tarefa_projeto_user')
            ->whereIn('tarefa_projeto_id', $tarefas->pluck('id'))
            ->get()->groupBy('tarefa_projeto_id');
        $resources = DB::table('users')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($u) => ['id' => (string) $u->id, 'name' => $u->name])->values();

        // Meio-dia evita shift de fuso ao virar timestamp ms.
        $toMs = function ($date) {
            if (! $date) {
                return null;
            }
            return \Carbon\Carbon::parse($date)->setTime(12, 0, 0)->getTimestamp() * 1000;
        };

        $ganttTasks = $tarefas->map(function ($t) use ($rowById, $pivot, $toMs) {
            $depends = collect(preg_split('/[^0-9]+/', (string) $t->dependencias, -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($id) => $rowById[$id] ?? null)
                ->filter()->implode(',');

            $assigs = [];
            $n = 0;
            foreach (($pivot[$t->id] ?? collect()) as $p) {
                $assigs[] = [
                    'id' => 'tmp_as_' . $t->id . '_' . (++$n),
                    'resourceId' => (string) $p->user_id,
                    'roleId' => '1',
                    'effort' => 0,
                ];
            }

            $start = $toMs($t->dt_inicio);
            $end = $toMs($t->dt_fim);
            // O jQueryGantt DESCARTA tarefas de período zero (start==end) no load, o que
            // desalinha as dependências (baseadas em linha). Garante fim > início (+1 dia)
            // — o marco continua marcado via startIsMilestone/endIsMilestone.
            if ($start !== null && ($end === null || $end <= $start)) {
                $end = $start + 86400000;
            }

            return [
                'id' => (string) $t->id,
                'name' => $t->nome,
                'code' => $t->code ?? '',
                'level' => (int) $t->level,
                'status' => $t->status ?: 'STATUS_ACTIVE',
                'start' => $start,
                'duration' => max(1, (int) ($t->tempo_duracao ?? 1)),
                'end' => $end,
                // jQueryGantt: um marco tem data FIXA e conflita com dependências que o
                // moveriam (erro START/END_IS_MILESTONE). Só mantém a marca de marco quando
                // a tarefa não tem dependências.
                'startIsMilestone' => $depends === '' && $t->startIsMilestone === 'true',
                'endIsMilestone' => $depends === '' && $t->endIsMilestone === 'true',
                'collapsed' => $t->collapsed === 'true',
                'progress' => (int) $t->progresso,
                'progressByWorklog' => false,
                'relevance' => 0,
                'type' => '',
                'typeId' => '',
                'description' => '',
                'depends' => $depends,
                'assigs' => $assigs,
                'hasChild' => $t->hasChild === 'true',
                'canWrite' => true,
            ];
        })->values();

        return [
            'tasks' => $ganttTasks,
            'selectedRow' => 0,
            'deletedTaskIds' => [],
            'resources' => $resources,
            'roles' => [['id' => '1', 'name' => 'Responsável']],
            'canWrite' => true,
            'canDelete' => true,
            'canWriteOnParent' => true,
            'canAdd' => true,
        ];
    }

    /**
     * Recebe o JSON do projeto do jQueryGantt (GanttMaster.saveGantt) e persiste
     * em sts_tarefas_projeto: upsert de tarefas, dependências (linha -> id),
     * responsáveis (assigs -> pivot) e exclusões (deletedTaskIds).
     */
    public function saveGantt(Request $request, Projeto $projeto)
    {
        $this->authorize('update', $projeto);

        $tasks = $request->input('tasks', []);
        $deleted = $request->input('deletedTaskIds', []);

        DB::beginTransaction();
        try {
            $companyId = $projeto->company_id;
            $userId = auth()->id();
            $now = now()->toDateTimeString();

            // 1) Exclusões (só tarefas deste projeto).
            $deletedNumeric = array_values(array_filter($deleted, 'is_numeric'));
            if ($deletedNumeric) {
                $ids = DB::table('sts_tarefas_projeto')
                    ->where('projeto_id', $projeto->id)->whereIn('id', $deletedNumeric)->pluck('id');
                if ($ids->isNotEmpty()) {
                    DB::table('tarefa_projeto_user')->whereIn('tarefa_projeto_id', $ids)->delete();
                    DB::table('sts_tarefas_projeto')->whereIn('id', $ids)->delete();
                }
            }

            // 2) Upsert. Mapeia linha (1-based) -> id real (inclui tarefas novas 'tmp_').
            $rowToId = [];
            $idMap = [];   // id temporário do cliente (tmp_) -> id real (ressincroniza sem recarregar)
            $ordem = 1;
            foreach ($tasks as $i => $t) {
                $isNew = ! isset($t['id']) || ! is_numeric($t['id']);
                $payload = [
                    'nome' => mb_substr($t['name'] ?? 'Tarefa', 0, 100),
                    'code' => isset($t['code']) ? mb_substr($t['code'], 0, 250) : null,
                    'level' => (int) ($t['level'] ?? 1),
                    'status' => $t['status'] ?? 'STATUS_ACTIVE',
                    'progresso' => (int) ($t['progress'] ?? 0),
                    // +12h/-12h: extrai o dia pretendido independente do fuso do navegador.
                    'dt_inicio' => isset($t['start']) ? \Carbon\Carbon::createFromTimestampMs($t['start'] + 43200000, 'UTC')->format('Y-m-d') : null,
                    'dt_fim' => isset($t['end']) ? \Carbon\Carbon::createFromTimestampMs($t['end'] - 43200000, 'UTC')->format('Y-m-d') : null,
                    'tempo_duracao' => (int) ($t['duration'] ?? 0),
                    'startIsMilestone' => ! empty($t['startIsMilestone']) ? 'true' : 'false',
                    'endIsMilestone' => ! empty($t['endIsMilestone']) ? 'true' : 'false',
                    'collapsed' => ! empty($t['collapsed']) ? 'true' : 'false',
                    'hasChild' => ! empty($t['hasChild']) ? 'true' : 'false',
                    'ordem' => $ordem++,
                    'user_update_id' => $userId,
                    'modified' => $now,
                ];

                if ($isNew) {
                    $payload += [
                        'projeto_id' => $projeto->id,
                        'company_id' => $companyId,
                        'descricao' => '', 'type' => 0, 'typeId' => 0, 'assigs' => 0,
                        'adms_cor_id' => 1, 'adms_sit_id' => 1, 'adms_usuario_id' => $userId,
                        'cor_prioridade_id' => 7, 'dependencias' => '', 'created' => $now,
                    ];
                    $newId = DB::table('sts_tarefas_projeto')->insertGetId($payload);
                    $rowToId[$i + 1] = $newId;
                    if (isset($t['id'])) {
                        $idMap[$t['id']] = $newId;
                    }
                } else {
                    DB::table('sts_tarefas_projeto')
                        ->where('id', $t['id'])->where('projeto_id', $projeto->id)->update($payload);
                    $rowToId[$i + 1] = (int) $t['id'];
                }
            }

            // 3) Dependências (linha -> id) e responsáveis (assigs -> pivot).
            foreach ($tasks as $i => $t) {
                $taskId = $rowToId[$i + 1];

                $depIds = collect(preg_split('/\s*,\s*/', (string) ($t['depends'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
                    ->map(fn ($d) => $rowToId[(int) explode(':', $d)[0]] ?? null)
                    ->filter()->implode(',');
                DB::table('sts_tarefas_projeto')->where('id', $taskId)->update(['dependencias' => $depIds]);

                DB::table('tarefa_projeto_user')->where('tarefa_projeto_id', $taskId)->delete();
                $seen = [];
                foreach (($t['assigs'] ?? []) as $a) {
                    $uid = $a['resourceId'] ?? null;
                    if (is_numeric($uid) && ! in_array($uid, $seen, true)) {
                        $seen[] = $uid;
                        DB::table('tarefa_projeto_user')->insert([
                            'tarefa_projeto_id' => $taskId, 'user_id' => (int) $uid,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }

            DB::commit();

            // Devolve só o mapa de IDs (tmp -> real): o cliente ressincroniza as tarefas
            // novas no lugar, SEM recarregar o Gantt (não re-renderiza / não "buga" a view).
            return response()->json(['ok' => true, 'idMap' => $idMap]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gantt save (projeto ' . $projeto->id . '): ' . $e->getMessage());

            return response()->json(['ok' => false, 'message' => 'Erro ao salvar o cronograma.'], 500);
        }
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
                $validated['imagem_capa'] = $request->file('imagem_capa')->store('projetos/capas', 'local');
            }

            if ($request->hasFile('arquivos_anexos')) {
                $paths = [];
                foreach ($request->file('arquivos_anexos') as $file) {
                    $paths[] = $file->store('projetos/anexos', 'local');
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
            'kanbanColunas.tarefas.users', // Load assigned users
            'tarefas',
            'membros' // Load project members to populate the assignment dropdown
        ]);

        if ($projeto->kanbanColunas->isEmpty()) {
            $col1 = $projeto->kanbanColunas()->create(['nome' => 'A Fazer', 'ordem' => 1]);
            $projeto->kanbanColunas()->create(['nome' => 'Em Andamento', 'ordem' => 2]);
            $projeto->kanbanColunas()->create(['nome' => 'Concluído', 'ordem' => 3]);
            
            // Atribui tarefas órfãs à primeira coluna
            $projeto->tarefas()->whereNull('kanban_coluna_id')->update(['kanban_coluna_id' => $col1->id]);
            
            $projeto->load('kanbanColunas.tarefas.users'); // Reload
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
                $validated['imagem_capa'] = $request->file('imagem_capa')->store('projetos/capas', 'local');
            }

            if ($request->hasFile('arquivos_anexos')) {
                $paths = is_array($projeto->arquivos_anexos) ? $projeto->arquivos_anexos : [];
                foreach ($request->file('arquivos_anexos') as $file) {
                    $paths[] = $file->store('projetos/anexos', 'local');
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
