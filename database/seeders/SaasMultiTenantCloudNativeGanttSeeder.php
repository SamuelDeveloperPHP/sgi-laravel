<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cadastra, sob demanda, o planejamento Gantt do projeto de portfolio:
 * SaaS Multi-Tenant Cloud-Native em Python.
 *
 * Rode explicitamente:
 * php artisan db:seed --class=SaasMultiTenantCloudNativeGanttSeeder
 */
class SaasMultiTenantCloudNativeGanttSeeder extends Seeder
{
    private const PROJECT_NAME = 'SaaS Multi-Tenant Cloud-Native - Python';
    private const PROJECT_REPOSITORY = 'https://github.com/SamuelDeveloperPHP/Phyton---SaaS-Multi-Tenant-Cloud-Native';

    public function run(): void
    {
        $companyId = $this->resolveCompanyId();
        $userId = $this->resolveUserId();
        $now = now()->toDateTimeString();

        DB::transaction(function () use ($companyId, $userId, $now): void {
            $projectId = $this->upsertProject($companyId, $userId, $now);
            $taskIds = [];

            foreach ($this->tasks() as $order => $task) {
                $taskIds[$task['code']] = $this->upsertTask(
                    projectId: $projectId,
                    companyId: $companyId,
                    userId: $userId,
                    now: $now,
                    order: $order + 1,
                    task: $task,
                );

                $this->ensureTaskResponsible($taskIds[$task['code']], $userId, $now);
                $this->ensureChecklists($taskIds[$task['code']], $companyId, $userId, $now, $task);
            }

            foreach ($this->tasks() as $task) {
                $dependencies = collect($task['deps'])
                    ->map(fn (string $code): ?int => $taskIds[$code] ?? null)
                    ->filter()
                    ->implode(',');

                DB::table('sts_tarefas_projeto')
                    ->where('id', $taskIds[$task['code']])
                    ->update([
                        'dependencias' => $dependencies,
                        'modified' => $now,
                    ]);
            }

            $this->ensureProjectMember($projectId, $userId, $now);

            $this->command?->info(sprintf(
                "Projeto '%s' atualizado (id %s) com %s tarefas/checkpoints.",
                self::PROJECT_NAME,
                $projectId,
                count($this->tasks()),
            ));
        });
    }

    private function resolveCompanyId(): int
    {
        $companyId = DB::table('companies')->where('id', 1)->value('id')
            ?? DB::table('companies')->orderBy('id')->value('id');

        if (! $companyId) {
            throw new RuntimeException('Nenhuma empresa encontrada para vincular o projeto Gantt.');
        }

        return (int) $companyId;
    }

    private function resolveUserId(): int
    {
        $userId = DB::table('users')->where('id', 1)->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (! $userId) {
            throw new RuntimeException('Nenhum usuario encontrado para assumir a responsabilidade do projeto.');
        }

        return (int) $userId;
    }

    private function upsertProject(int $companyId, int $userId, string $now): int
    {
        $data = [
            'descricao' => $this->projectDescription(),
            'data_inicio' => '2026-08-10',
            'data_fim' => '2026-11-13',
            'ativo' => '1',
            'porc_concluido' => 0,
            'ordem' => 0,
            'nivel_prioridade_id' => 1,
            'cor_prioridade_id' => 10,
            'adms_cor_id' => 1,
            'adms_sit_id' => 1,
            'adms_usuario_id' => $userId,
            'user_update_id' => $userId,
            'responsavel_id' => $userId,
            'privacidade' => 'Private',
            'tags' => json_encode([
                'portfolio-senior',
                'python',
                'fastapi',
                'nextjs',
                'multi-tenant',
                'cloud-native',
            ], JSON_THROW_ON_ERROR),
            'company_id' => $companyId,
            'modified' => $now,
        ];

        $existing = DB::table('sts_projetos')
            ->where('company_id', $companyId)
            ->where('nomeProjeto', self::PROJECT_NAME)
            ->first();

        if ($existing) {
            DB::table('sts_projetos')->where('id', $existing->id)->update($data);

            return (int) $existing->id;
        }

        return (int) DB::table('sts_projetos')->insertGetId($data + [
            'nomeProjeto' => self::PROJECT_NAME,
            'created' => $now,
        ]);
    }

    private function upsertTask(
        int $projectId,
        int $companyId,
        int $userId,
        string $now,
        int $order,
        array $task,
    ): int {
        $duration = $this->duration($task['start'], $task['end'], $task['milestone'] ?? false);

        $data = [
            'nome' => $task['name'],
            'descricao' => $task['description'] ?? '',
            'ordem' => $order,
            'progresso' => $task['progress'] ?? 0,
            'type' => 0,
            'typeId' => 0,
            'assigs' => 0,
            'level' => $task['level'],
            'status' => 'STATUS_ACTIVE',
            'dt_inicio' => $task['start'],
            'tempo_duracao' => $duration,
            'dt_fim' => $task['end'],
            'startIsMilestone' => ($task['milestone'] ?? false) ? 'true' : 'false',
            'endIsMilestone' => ($task['milestone'] ?? false) ? 'true' : 'false',
            'collapsed' => ($task['collapsed'] ?? false) ? 'true' : 'false',
            'hasChild' => ($task['group'] ?? false) ? 'true' : 'false',
            'tags' => json_encode($task['tags'] ?? [], JSON_THROW_ON_ERROR),
            'adms_cor_id' => 1,
            'adms_sit_id' => 1,
            'adms_usuario_id' => $userId,
            'user_update_id' => $userId,
            'cor_prioridade_id' => $task['priority'] ?? 7,
            'projeto_id' => $projectId,
            'company_id' => $companyId,
            'modified' => $now,
        ];

        $existing = DB::table('sts_tarefas_projeto')
            ->where('projeto_id', $projectId)
            ->where('code', $task['code'])
            ->first();

        if ($existing) {
            DB::table('sts_tarefas_projeto')->where('id', $existing->id)->update($data);

            return (int) $existing->id;
        }

        return (int) DB::table('sts_tarefas_projeto')->insertGetId($data + [
            'code' => $task['code'],
            'dependencias' => '',
            'created' => $now,
        ]);
    }

    private function ensureTaskResponsible(int $taskId, int $userId, string $now): void
    {
        $exists = DB::table('tarefa_projeto_user')
            ->where('tarefa_projeto_id', $taskId)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('tarefa_projeto_user')->insert([
            'tarefa_projeto_id' => $taskId,
            'user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureProjectMember(int $projectId, int $userId, string $now): void
    {
        DB::table('projeto_user')->updateOrInsert(
            [
                'projeto_id' => $projectId,
                'user_id' => $userId,
            ],
            [
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    private function ensureChecklists(int $taskId, int $companyId, int $userId, string $now, array $task): void
    {
        $items = $task['checklist'] ?? $this->defaultChecklist($task);

        foreach (array_values($items) as $index => $description) {
            DB::table('sts_tarefas_checklists')->updateOrInsert(
                [
                    'tarefa_projeto_id' => $taskId,
                    'descricao' => $description,
                ],
                [
                    'concluido' => false,
                    'ordem' => $index + 1,
                    'company_id' => $companyId,
                    'user_create' => $userId,
                    'user_edit' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    private function duration(string $start, string $end, bool $milestone): int
    {
        if ($milestone) {
            return 0;
        }

        return max(1, CarbonImmutable::parse($start)->diffInDays(CarbonImmutable::parse($end)));
    }

    private function defaultChecklist(array $task): array
    {
        if ($task['level'] === 1 && ($task['group'] ?? false)) {
            return [
                'Validar escopo e criterios de aceite da fase.',
                'Registrar decisoes tecnicas em ADR quando aplicavel.',
                'Manter evidencias de homologacao anexadas ao projeto.',
            ];
        }

        if ($task['milestone'] ?? false) {
            return [
                'Validar entregaveis obrigatorios do marco.',
                'Registrar evidencia objetiva no README ou release notes.',
                'Confirmar que nada depende de dados apagados manualmente.',
            ];
        }

        return [
            'Implementar com tratamento de erro tecnico e mensagem amigavel.',
            'Adicionar feedback de sucesso/erro para o usuario.',
            'Registrar log/auditoria quando houver acao sensivel.',
        ];
    }

    private function projectDescription(): string
    {
        return implode('', [
            '<p>Planejamento de homologacao para o portfolio senior em Python: SaaS Multi-Tenant Cloud-Native.</p>',
            '<p>Repositorio: <a href="', self::PROJECT_REPOSITORY, '" target="_blank">', self::PROJECT_REPOSITORY, '</a></p>',
            '<p>Stack base: Python, FastAPI, PostgreSQL, Redis, Celery, Next.js, React, TypeScript, Tailwind CSS, shadcn/ui, Docker, Nginx, Kubernetes, Terraform, GitHub Actions, OpenTelemetry, Prometheus e Grafana.</p>',
            '<p>Padrao obrigatorio: CRUDs com tratamento de erro, notificacoes de sucesso/erro, bloqueio de tela em POST/PUT/DELETE, isolamento multi-tenant e auditoria de acoes do usuario.</p>',
        ]);
    }

    private function tasks(): array
    {
        return [
            [
                'code' => 'A',
                'level' => 1,
                'name' => 'Governanca tecnica e stack',
                'start' => '2026-08-10',
                'end' => '2026-08-14',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['arquitetura', 'stack'],
            ],
            [
                'code' => 'A1',
                'level' => 2,
                'name' => 'Definir stack e ADR principal',
                'start' => '2026-08-10',
                'end' => '2026-08-11',
                'deps' => [],
                'description' => 'Escolha oficial da stack e registro do motivo tecnico para recrutadores.',
                'tags' => ['adr', 'stack'],
                'checklist' => [
                    'Registrar decisao FastAPI para API-first SaaS.',
                    'Registrar decisao Next.js React TypeScript para frontend.',
                    'Documentar trade-offs entre Django, FastAPI, React, Vue e Angular.',
                ],
            ],
            [
                'code' => 'A2',
                'level' => 2,
                'name' => 'Criar padrao de repositorio e convencoes',
                'start' => '2026-08-11',
                'end' => '2026-08-12',
                'deps' => ['A1'],
                'description' => 'Estrutura limpa para backend, frontend, infra, docs e scripts.',
                'tags' => ['repo', 'conventions'],
                'checklist' => [
                    'Definir pastas backend, frontend, infra, docs e scripts.',
                    'Padronizar conventional commits e pull request checklist.',
                    'Configurar README inicial com arquitetura e como rodar.',
                ],
            ],
            [
                'code' => 'A3',
                'level' => 2,
                'name' => 'Definir padrao de homologacao',
                'start' => '2026-08-12',
                'end' => '2026-08-13',
                'deps' => ['A2'],
                'description' => 'Ambiente local deve imitar producao: containers, envs, logs, filas, email e banco.',
                'tags' => ['homologacao', 'devops'],
                'checklist' => [
                    'Separar .env.example para local, homologacao e producao.',
                    'Garantir Docker Compose com API, web, DB, Redis, Mailpit e Nginx.',
                    'Definir dados seed de demonstracao sem apagar dados existentes.',
                ],
            ],
            [
                'code' => 'A4',
                'level' => 2,
                'name' => 'Fechar criterios de aceite do produto',
                'start' => '2026-08-13',
                'end' => '2026-08-14',
                'deps' => ['A3'],
                'description' => 'Checklist de qualidade que todo CRUD e fluxo sensivel deve obedecer.',
                'tags' => ['qualidade', 'homologacao'],
                'checklist' => [
                    'Todo CRUD deve ter sucesso, erro e validacao visiveis ao usuario.',
                    'POST, PUT e DELETE devem bloquear interacao ate finalizar recarga ou revalidacao.',
                    'Toda acao sensivel deve gerar auditoria com usuario, tenant, recurso e payload seguro.',
                ],
            ],
            [
                'code' => 'M1',
                'level' => 1,
                'name' => 'Marco: stack e padroes aprovados',
                'start' => '2026-08-14',
                'end' => '2026-08-14',
                'milestone' => true,
                'deps' => ['A4'],
                'tags' => ['milestone'],
            ],
            [
                'code' => 'B',
                'level' => 1,
                'name' => 'Arquitetura multi-tenant e seguranca',
                'start' => '2026-08-17',
                'end' => '2026-08-28',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['tenant', 'security'],
            ],
            [
                'code' => 'B1',
                'level' => 2,
                'name' => 'Modelar tenant, empresa, usuario e modulo',
                'start' => '2026-08-17',
                'end' => '2026-08-18',
                'deps' => ['M1'],
                'description' => 'Modelo central para isolar dados por empresa e permitir administracao master.',
                'tags' => ['database', 'tenant'],
                'checklist' => [
                    'Criar entidades Company, User, Module, Role e Permission.',
                    'Definir usuario administrador por empresa.',
                    'Definir administrador master com acesso global auditado.',
                ],
            ],
            [
                'code' => 'B2',
                'level' => 2,
                'name' => 'Definir isolamento por tenant e RLS',
                'start' => '2026-08-18',
                'end' => '2026-08-20',
                'deps' => ['B1'],
                'description' => 'Defesa em profundidade: filtros de aplicacao e PostgreSQL Row Level Security.',
                'tags' => ['tenant', 'postgresql', 'rls'],
                'checklist' => [
                    'Criar TenantContext obrigatorio por request autenticada.',
                    'Aplicar filtros automaticos por company_id em queries sensiveis.',
                    'Planejar Row Level Security em tabelas criticas do PostgreSQL.',
                ],
            ],
            [
                'code' => 'B3',
                'level' => 2,
                'name' => 'Desenhar RBAC e escopo de permissoes',
                'start' => '2026-08-20',
                'end' => '2026-08-21',
                'deps' => ['B2'],
                'description' => 'Permissoes separadas por tenant, modulo e papel de negocio.',
                'tags' => ['rbac', 'permissions'],
                'checklist' => [
                    'Separar Master Admin, Company Admin e usuarios comuns.',
                    'Garantir que admin de uma empresa nao leia outra empresa.',
                    'Criar matriz de permissoes por recurso e modulo.',
                ],
            ],
            [
                'code' => 'B4',
                'level' => 2,
                'name' => 'Regras de dominio empresarial e usuario publico',
                'start' => '2026-08-21',
                'end' => '2026-08-25',
                'deps' => ['B3'],
                'description' => 'Cadastro corporativo exige dominio da empresa; usuario publico recebe acesso temporario.',
                'tags' => ['onboarding', 'security'],
                'checklist' => [
                    'Bloquear cadastro corporativo fora do dominio da empresa.',
                    'Permitir gmail, hotmail, yahoo e dominios publicos somente como conta publica.',
                    'Expirar conta publica em 15 dias por padrao e bloquear acesso automaticamente.',
                ],
            ],
            [
                'code' => 'B5',
                'level' => 2,
                'name' => 'Politica de modulos publico, trial e privado',
                'start' => '2026-08-25',
                'end' => '2026-08-27',
                'deps' => ['B4'],
                'description' => 'Master Admin define modulo publico, trial 15 dias, trial 30 dias ou privado.',
                'tags' => ['modules', 'access'],
                'checklist' => [
                    'Criar configuracao de modulo publico, trial 15, trial 30 e privado.',
                    'Aplicar modulos padrao no cadastro de empresas.',
                    'Aplicar modulos permitidos para usuarios publicos conforme prazo.',
                ],
            ],
            [
                'code' => 'B6',
                'level' => 2,
                'name' => 'Especificar testes de vazamento cross-tenant',
                'start' => '2026-08-27',
                'end' => '2026-08-28',
                'deps' => ['B5'],
                'description' => 'Testes negativos garantem que um tenant nao acessa dados de outro.',
                'tags' => ['tests', 'tenant'],
                'checklist' => [
                    'Criar fixtures de duas empresas com admins e usuarios distintos.',
                    'Testar leitura, criacao, edicao e exclusao cruzada bloqueada.',
                    'Validar bypass apenas para Master Admin com auditoria.',
                ],
            ],
            [
                'code' => 'M2',
                'level' => 1,
                'name' => 'Marco: arquitetura multi-tenant validada',
                'start' => '2026-08-28',
                'end' => '2026-08-28',
                'milestone' => true,
                'deps' => ['B6'],
                'tags' => ['milestone'],
            ],
            [
                'code' => 'C',
                'level' => 1,
                'name' => 'Backend API Python',
                'start' => '2026-08-31',
                'end' => '2026-09-18',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['backend', 'fastapi'],
            ],
            [
                'code' => 'C1',
                'level' => 2,
                'name' => 'Bootstrap FastAPI e configuracoes',
                'start' => '2026-08-31',
                'end' => '2026-09-02',
                'deps' => ['M2'],
                'description' => 'Base Python com settings tipadas, logging e health checks.',
                'tags' => ['fastapi', 'pydantic'],
                'checklist' => [
                    'Configurar FastAPI com Pydantic Settings.',
                    'Criar health check e readiness check.',
                    'Configurar logs estruturados com correlation id.',
                ],
            ],
            [
                'code' => 'C2',
                'level' => 2,
                'name' => 'SQLAlchemy, Alembic e PostgreSQL',
                'start' => '2026-09-02',
                'end' => '2026-09-04',
                'deps' => ['C1'],
                'description' => 'Camada de persistencia com migrations versionadas e modelos auditaveis.',
                'tags' => ['postgresql', 'sqlalchemy'],
                'checklist' => [
                    'Criar base declarativa SQLAlchemy 2.',
                    'Configurar Alembic com naming convention.',
                    'Criar mixins de tenant, timestamps e auditoria.',
                ],
            ],
            [
                'code' => 'C3',
                'level' => 2,
                'name' => 'Autenticacao, sessao e refresh token',
                'start' => '2026-09-04',
                'end' => '2026-09-08',
                'deps' => ['C2'],
                'description' => 'Login seguro com JWT, refresh token rotativo e revogacao.',
                'tags' => ['auth', 'security'],
                'checklist' => [
                    'Implementar login com senha hasheada por Argon2.',
                    'Criar refresh token rotativo e revogavel.',
                    'Registrar tentativas de login e bloqueios de seguranca.',
                ],
            ],
            [
                'code' => 'C4',
                'level' => 2,
                'name' => 'CRUD de empresas e usuarios',
                'start' => '2026-09-08',
                'end' => '2026-09-10',
                'deps' => ['C3'],
                'description' => 'CRUD com validacoes de tenant, dominio empresarial e administradores.',
                'tags' => ['crud', 'users'],
                'checklist' => [
                    'Criar endpoints de empresas com isolamento por tenant.',
                    'Criar endpoints de usuarios com validacao de dominio.',
                    'Retornar erros padronizados para validacao, regra de negocio e excecao.',
                ],
            ],
            [
                'code' => 'C5',
                'level' => 2,
                'name' => 'CRUD de modulos, planos e trials',
                'start' => '2026-09-10',
                'end' => '2026-09-14',
                'deps' => ['C4'],
                'description' => 'Modulo controla acesso padrao para empresas e contas publicas temporarias.',
                'tags' => ['modules', 'crud'],
                'checklist' => [
                    'Cadastrar modulo como publico, trial 15, trial 30 ou privado.',
                    'Permitir Master Admin atribuir modulos para empresas.',
                    'Bloquear acesso quando trial expirar ou modulo estiver indisponivel.',
                ],
            ],
            [
                'code' => 'C6',
                'level' => 2,
                'name' => 'Auditoria de acoes e eventos de dominio',
                'start' => '2026-09-14',
                'end' => '2026-09-16',
                'deps' => ['C5'],
                'description' => 'Toda alteracao relevante deve ser rastreavel por usuario, empresa, recurso e payload seguro.',
                'tags' => ['audit', 'logs'],
                'checklist' => [
                    'Auditar POST, PUT, PATCH e DELETE.',
                    'Registrar antes/depois sem vazar senha, token ou dado sensivel.',
                    'Expor consulta de auditoria somente para perfis autorizados.',
                ],
            ],
            [
                'code' => 'C7',
                'level' => 2,
                'name' => 'Contrato OpenAPI e tratamento global de erros',
                'start' => '2026-09-16',
                'end' => '2026-09-18',
                'deps' => ['C6'],
                'description' => 'API documentada, erros previsiveis e exemplos para avaliacao tecnica.',
                'tags' => ['openapi', 'errors'],
                'checklist' => [
                    'Documentar responses 200, 201, 400, 401, 403, 404 e 422.',
                    'Criar exception handlers globais.',
                    'Publicar Swagger/ReDoc funcional em homologacao.',
                ],
            ],
            [
                'code' => 'M3',
                'level' => 1,
                'name' => 'Marco: API MVP segura',
                'start' => '2026-09-18',
                'end' => '2026-09-18',
                'milestone' => true,
                'deps' => ['C7'],
                'tags' => ['milestone'],
            ],
            [
                'code' => 'D',
                'level' => 1,
                'name' => 'Frontend SaaS Next.js',
                'start' => '2026-09-07',
                'end' => '2026-09-25',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['frontend', 'nextjs'],
            ],
            [
                'code' => 'D1',
                'level' => 2,
                'name' => 'Criar app shell e rotas protegidas',
                'start' => '2026-09-07',
                'end' => '2026-09-09',
                'deps' => ['C1'],
                'description' => 'Base visual do SaaS com layout autenticado, tenant ativo e navegacao por modulo.',
                'tags' => ['nextjs', 'routing'],
                'checklist' => [
                    'Criar layout autenticado com tenant atual visivel.',
                    'Criar guards por autenticacao e permissao.',
                    'Mostrar somente modulos liberados para o usuario.',
                ],
            ],
            [
                'code' => 'D2',
                'level' => 2,
                'name' => 'Design system Tailwind e shadcn',
                'start' => '2026-09-09',
                'end' => '2026-09-11',
                'deps' => ['D1'],
                'description' => 'UI profissional, consistente, responsiva e sem aspecto de prototipo.',
                'tags' => ['ui', 'tailwind'],
                'checklist' => [
                    'Configurar Tailwind CSS e tokens de design.',
                    'Criar componentes base com shadcn/ui.',
                    'Padronizar tabelas, formularios, dialogs, empty states e skeletons.',
                ],
            ],
            [
                'code' => 'D3',
                'level' => 2,
                'name' => 'Fluxos de login, cadastro e onboarding',
                'start' => '2026-09-11',
                'end' => '2026-09-15',
                'deps' => ['D2', 'C3'],
                'description' => 'Entrada no produto cobrindo empresa, usuario publico e validacao de dominio.',
                'tags' => ['auth', 'onboarding'],
                'checklist' => [
                    'Criar login com mensagens claras de erro.',
                    'Criar cadastro de empresa com administrador inicial.',
                    'Criar cadastro publico sem CNPJ com aviso de expiracao.',
                ],
            ],
            [
                'code' => 'D4',
                'level' => 2,
                'name' => 'Padrao CRUD com feedback e bloqueio de tela',
                'start' => '2026-09-15',
                'end' => '2026-09-17',
                'deps' => ['D3'],
                'description' => 'Todos os formularios usam estados transacionais previsiveis e seguros.',
                'tags' => ['crud', 'ux'],
                'checklist' => [
                    'Bloquear tela ou formulario durante POST, PUT, PATCH e DELETE.',
                    'Exibir toast de sucesso com proxima acao clara.',
                    'Exibir erro amigavel sem perder detalhes tecnicos nos logs.',
                ],
            ],
            [
                'code' => 'D5',
                'level' => 2,
                'name' => 'Telas de empresas, usuarios e permissoes',
                'start' => '2026-09-17',
                'end' => '2026-09-21',
                'deps' => ['D4', 'C4', 'C5'],
                'description' => 'CRUDs principais visiveis para recrutadores avaliarem isolamento e permissoes.',
                'tags' => ['crud', 'permissions'],
                'checklist' => [
                    'Criar listagem com filtro, paginacao e empty state.',
                    'Criar formularios com validacao client/server.',
                    'Mostrar acoes indisponiveis como desabilitadas ou ocultas conforme permissao.',
                ],
            ],
            [
                'code' => 'D6',
                'level' => 2,
                'name' => 'Dashboard master, admin e usuario',
                'start' => '2026-09-21',
                'end' => '2026-09-23',
                'deps' => ['D5'],
                'description' => 'Cada perfil enxerga um painel coerente com suas permissoes e escopo.',
                'tags' => ['dashboard', 'tenant'],
                'checklist' => [
                    'Master Admin visualiza empresas e modulos globais.',
                    'Administrador da empresa visualiza somente sua empresa.',
                    'Usuario comum visualiza somente seus modulos e registros permitidos.',
                ],
            ],
            [
                'code' => 'D7',
                'level' => 2,
                'name' => 'Estados de erro, loading e acessibilidade',
                'start' => '2026-09-23',
                'end' => '2026-09-25',
                'deps' => ['D6'],
                'description' => 'Tratamento profissional de falhas, loading, foco e navegacao por teclado.',
                'tags' => ['a11y', 'ux'],
                'checklist' => [
                    'Criar error boundary e fallback por pagina.',
                    'Adicionar loading skeleton e estado vazio em todas as listas.',
                    'Validar foco, contraste e navegacao por teclado.',
                ],
            ],
            [
                'code' => 'M4',
                'level' => 1,
                'name' => 'Marco: frontend beta navegavel',
                'start' => '2026-09-25',
                'end' => '2026-09-25',
                'milestone' => true,
                'deps' => ['D7'],
                'tags' => ['milestone'],
            ],
            [
                'code' => 'E',
                'level' => 1,
                'name' => 'Redis, filas e email',
                'start' => '2026-09-21',
                'end' => '2026-10-02',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['redis', 'mail'],
            ],
            [
                'code' => 'E1',
                'level' => 2,
                'name' => 'Redis para cache, sessao e rate limit',
                'start' => '2026-09-21',
                'end' => '2026-09-23',
                'deps' => ['C2'],
                'description' => 'Redis usado em fluxos reais, nao apenas como container decorativo.',
                'tags' => ['redis', 'cache'],
                'checklist' => [
                    'Configurar cache de permissoes e modulos por tenant.',
                    'Configurar rate limit por usuario, IP e tenant.',
                    'Criar comando de diagnostico para validar Redis em homologacao.',
                ],
            ],
            [
                'code' => 'E2',
                'level' => 2,
                'name' => 'Celery para jobs assincronos',
                'start' => '2026-09-23',
                'end' => '2026-09-25',
                'deps' => ['E1'],
                'description' => 'Jobs de email, expiracao e auditoria assinc devem rodar fora da request.',
                'tags' => ['celery', 'queue'],
                'checklist' => [
                    'Configurar Celery worker com Redis broker.',
                    'Criar retry/backoff para tarefas de email.',
                    'Registrar falha de job com correlation id.',
                ],
            ],
            [
                'code' => 'E3',
                'level' => 2,
                'name' => 'Mailpit local e provider SMTP',
                'start' => '2026-09-25',
                'end' => '2026-09-29',
                'deps' => ['E2'],
                'description' => 'Email testavel localmente e pronto para trocar provider em producao.',
                'tags' => ['mailpit', 'email'],
                'checklist' => [
                    'Configurar Mailpit no Docker Compose de homologacao.',
                    'Criar templates para convite, bloqueio e expiracao.',
                    'Documentar troca para SMTP real por variaveis de ambiente.',
                ],
            ],
            [
                'code' => 'E4',
                'level' => 2,
                'name' => 'Job de expiracao de contas publicas',
                'start' => '2026-09-29',
                'end' => '2026-10-01',
                'deps' => ['E3', 'B4'],
                'description' => 'Conta publica expira, bloqueia e recebe email automaticamente.',
                'tags' => ['trial', 'jobs'],
                'checklist' => [
                    'Criar scheduler para expirar acessos publicos.',
                    'Bloquear login depois da data de expiracao.',
                    'Enviar email informando bloqueio e motivo.',
                ],
            ],
            [
                'code' => 'E5',
                'level' => 2,
                'name' => 'Notificacoes de seguranca e auditoria',
                'start' => '2026-10-01',
                'end' => '2026-10-02',
                'deps' => ['E4'],
                'description' => 'Eventos sensiveis geram rastreabilidade e comunicacao adequada.',
                'tags' => ['notifications', 'audit'],
                'checklist' => [
                    'Notificar alteracao de permissao de modulo.',
                    'Notificar bloqueio de usuario publico.',
                    'Auditar envio, falha e reprocessamento de email.',
                ],
            ],
            [
                'code' => 'F',
                'level' => 1,
                'name' => 'Cloud-native e DevOps',
                'start' => '2026-10-05',
                'end' => '2026-10-23',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['cloud', 'devops'],
            ],
            [
                'code' => 'F1',
                'level' => 2,
                'name' => 'Docker multi-stage para API e frontend',
                'start' => '2026-10-05',
                'end' => '2026-10-07',
                'deps' => ['C7'],
                'description' => 'Imagens leves, reproduziveis e sem dependencia local.',
                'tags' => ['docker'],
                'checklist' => [
                    'Criar Dockerfile production-like para API.',
                    'Criar Dockerfile production-like para frontend.',
                    'Rodar containers como usuario nao-root quando possivel.',
                ],
            ],
            [
                'code' => 'F2',
                'level' => 2,
                'name' => 'Docker Compose de homologacao completa',
                'start' => '2026-10-07',
                'end' => '2026-10-09',
                'deps' => ['F1', 'E3'],
                'description' => 'Ambiente local com paridade: app, web, banco, cache, filas, email e proxy.',
                'tags' => ['docker-compose', 'homologacao'],
                'checklist' => [
                    'Subir api, frontend, nginx, postgres, redis, worker e mailpit.',
                    'Criar healthchecks e dependencias entre servicos.',
                    'Documentar comandos de reset seguro sem apagar dados por acidente.',
                ],
            ],
            [
                'code' => 'F3',
                'level' => 2,
                'name' => 'CI com lint, tipos, testes e build',
                'start' => '2026-10-09',
                'end' => '2026-10-13',
                'deps' => ['F2'],
                'description' => 'GitHub Actions valida qualidade antes do merge.',
                'tags' => ['github-actions', 'ci'],
                'checklist' => [
                    'Rodar Ruff, MyPy, Pytest, Vitest e Playwright no CI.',
                    'Publicar coverage e artefatos de teste.',
                    'Bloquear merge quando teste critico falhar.',
                ],
            ],
            [
                'code' => 'F4',
                'level' => 2,
                'name' => 'Infra as Code com Terraform',
                'start' => '2026-10-13',
                'end' => '2026-10-16',
                'deps' => ['F3'],
                'description' => 'Infra declarativa para demonstrar maturidade cloud-native.',
                'tags' => ['terraform', 'iac'],
                'checklist' => [
                    'Criar modulos para rede, banco, cache e aplicacao.',
                    'Separar variaveis de homologacao e producao.',
                    'Documentar plano sem expor credenciais.',
                ],
            ],
            [
                'code' => 'F5',
                'level' => 2,
                'name' => 'Kubernetes e Helm',
                'start' => '2026-10-16',
                'end' => '2026-10-20',
                'deps' => ['F4'],
                'description' => 'Deploy cloud-native com manifests revisaveis.',
                'tags' => ['kubernetes', 'helm'],
                'checklist' => [
                    'Criar charts para API, frontend, worker e scheduler.',
                    'Configurar probes, resources e autoscaling inicial.',
                    'Separar secrets, configmaps e ingress.',
                ],
            ],
            [
                'code' => 'F6',
                'level' => 2,
                'name' => 'Observabilidade com OpenTelemetry',
                'start' => '2026-10-20',
                'end' => '2026-10-23',
                'deps' => ['F5'],
                'description' => 'Logs, metricas e traces conectados por correlation id.',
                'tags' => ['observability', 'otel'],
                'checklist' => [
                    'Instrumentar API com OpenTelemetry.',
                    'Expor metricas para Prometheus.',
                    'Criar dashboards Grafana para API, filas e banco.',
                ],
            ],
            [
                'code' => 'M5',
                'level' => 1,
                'name' => 'Marco: homologacao cloud-native pronta',
                'start' => '2026-10-23',
                'end' => '2026-10-23',
                'milestone' => true,
                'deps' => ['F6'],
                'tags' => ['milestone'],
            ],
            [
                'code' => 'G',
                'level' => 1,
                'name' => 'Qualidade, seguranca e testes',
                'start' => '2026-10-19',
                'end' => '2026-11-06',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['quality', 'security'],
            ],
            [
                'code' => 'G1',
                'level' => 2,
                'name' => 'Testes unitarios e servicos Python',
                'start' => '2026-10-19',
                'end' => '2026-10-21',
                'deps' => ['C3'],
                'description' => 'Cobertura das regras de dominio sem depender da interface.',
                'tags' => ['pytest'],
                'checklist' => [
                    'Cobrir services de tenant, modulos e expiracao.',
                    'Cobrir validacoes de dominio empresarial.',
                    'Cobrir falhas esperadas com mensagens padronizadas.',
                ],
            ],
            [
                'code' => 'G2',
                'level' => 2,
                'name' => 'Testes de integracao da API',
                'start' => '2026-10-21',
                'end' => '2026-10-23',
                'deps' => ['C7'],
                'description' => 'Contrato real de API com banco, Redis e autorizacao.',
                'tags' => ['api-tests'],
                'checklist' => [
                    'Testar rotas autenticadas com perfis diferentes.',
                    'Testar 403 quando empresa tenta acessar outra empresa.',
                    'Testar erros 422 e mensagens de validacao.',
                ],
            ],
            [
                'code' => 'G3',
                'level' => 2,
                'name' => 'Testes frontend com Vitest',
                'start' => '2026-10-23',
                'end' => '2026-10-27',
                'deps' => ['D4'],
                'description' => 'Componentes validam loading, erro, sucesso e permissoes.',
                'tags' => ['vitest', 'frontend'],
                'checklist' => [
                    'Testar formularios com validacao client-side.',
                    'Testar toasts de sucesso e erro.',
                    'Testar bloqueio visual em mutacoes.',
                ],
            ],
            [
                'code' => 'G4',
                'level' => 2,
                'name' => 'E2E Playwright multi-tenant',
                'start' => '2026-10-27',
                'end' => '2026-10-30',
                'deps' => ['D7', 'B6'],
                'description' => 'Prova executavel de que tenants nao vazam dados entre si.',
                'tags' => ['playwright', 'tenant'],
                'checklist' => [
                    'Empresa A cria registro e Empresa B nao visualiza.',
                    'Admin de empresa nao acessa rotas de outra empresa.',
                    'Master Admin acessa globalmente e gera auditoria.',
                ],
            ],
            [
                'code' => 'G5',
                'level' => 2,
                'name' => 'Load test e limites operacionais',
                'start' => '2026-10-30',
                'end' => '2026-11-03',
                'deps' => ['F2'],
                'description' => 'Validar comportamento basico sob carga e limites de uso.',
                'tags' => ['performance', 'load-test'],
                'checklist' => [
                    'Criar cenario k6 ou Locust para login e CRUD principal.',
                    'Medir latencia p95 em homologacao.',
                    'Registrar gargalos e decisoes de otimizacao.',
                ],
            ],
            [
                'code' => 'G6',
                'level' => 2,
                'name' => 'SAST, dependencias e hardening',
                'start' => '2026-11-03',
                'end' => '2026-11-06',
                'deps' => ['G5'],
                'description' => 'Seguranca automatizada para dependencias, codigo e containers.',
                'tags' => ['security', 'sast'],
                'checklist' => [
                    'Rodar Bandit, pip-audit e npm audit no CI.',
                    'Adicionar headers de seguranca no Nginx.',
                    'Documentar ameacas principais e mitigacoes.',
                ],
            ],
            [
                'code' => 'H',
                'level' => 1,
                'name' => 'Portfolio, documentacao e release',
                'start' => '2026-11-02',
                'end' => '2026-11-13',
                'group' => true,
                'collapsed' => false,
                'deps' => [],
                'tags' => ['portfolio', 'docs'],
            ],
            [
                'code' => 'H1',
                'level' => 2,
                'name' => 'README senior e roteiro de avaliacao',
                'start' => '2026-11-02',
                'end' => '2026-11-04',
                'deps' => ['M4'],
                'description' => 'README deve conduzir recrutador pelo que prova senioridade.',
                'tags' => ['readme'],
                'checklist' => [
                    'Explicar problema, arquitetura e decisoes tecnicas.',
                    'Listar comandos para subir homologacao.',
                    'Criar roteiro de demo para Master Admin, Admin e usuario comum.',
                ],
            ],
            [
                'code' => 'H2',
                'level' => 2,
                'name' => 'Diagramas de arquitetura e fluxo',
                'start' => '2026-11-04',
                'end' => '2026-11-06',
                'deps' => ['H1'],
                'description' => 'Diagramas simples ajudam avaliador a entender multi-tenant, filas e deploy.',
                'tags' => ['architecture', 'docs'],
                'checklist' => [
                    'Criar diagrama C4 de contexto e containers.',
                    'Criar diagrama do fluxo de autenticacao e tenant.',
                    'Criar diagrama de jobs, Redis e emails.',
                ],
            ],
            [
                'code' => 'H3',
                'level' => 2,
                'name' => 'Seeds de demonstracao sem apagar dados',
                'start' => '2026-11-06',
                'end' => '2026-11-10',
                'deps' => ['H2'],
                'description' => 'Dados de demo devem criar empresas, usuarios e cenarios de seguranca de forma idempotente.',
                'tags' => ['seeds', 'demo'],
                'checklist' => [
                    'Criar seed de Empresa A, Empresa B e usuarios por perfil.',
                    'Criar registros com prefixo de homologacao para testes.',
                    'Garantir que reexecutar seed nao apaga dados existentes.',
                ],
            ],
            [
                'code' => 'H4',
                'level' => 2,
                'name' => 'Evidencias, video curto e release notes',
                'start' => '2026-11-10',
                'end' => '2026-11-12',
                'deps' => ['H3'],
                'description' => 'Material final mostra funcionamento sem exigir longa explicacao.',
                'tags' => ['release', 'demo'],
                'checklist' => [
                    'Capturar telas dos fluxos principais.',
                    'Criar video ou GIF curto do fluxo multi-tenant.',
                    'Escrever release notes com riscos, limites e proximos passos.',
                ],
            ],
            [
                'code' => 'H5',
                'level' => 2,
                'name' => 'Publicar v1 para avaliacao no GitHub',
                'start' => '2026-11-12',
                'end' => '2026-11-13',
                'deps' => ['H4', 'G6', 'M5'],
                'description' => 'Versao final deve estar limpa, documentada e reproduzivel.',
                'tags' => ['github', 'release'],
                'checklist' => [
                    'Garantir main com CI verde.',
                    'Criar tag v1.0.0 e changelog.',
                    'Validar que secrets e dados sensiveis nao foram commitados.',
                ],
            ],
            [
                'code' => 'M6',
                'level' => 1,
                'name' => 'Marco: portfolio pronto para recrutadores',
                'start' => '2026-11-13',
                'end' => '2026-11-13',
                'milestone' => true,
                'deps' => ['H5'],
                'tags' => ['milestone'],
            ],
        ];
    }
}
