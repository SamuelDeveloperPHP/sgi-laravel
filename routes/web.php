<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AtaReuniaoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\ControleCalibracaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoRegistroController;
use App\Http\Controllers\EscopoController;
use App\Http\Controllers\FeriasController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FornecedorAvaliacaoController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\FornecedorDocumentoController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\HR\AreaController;
use App\Http\Controllers\HR\BeneficioController;
use App\Http\Controllers\HR\CargoController;
use App\Http\Controllers\HR\CandidatoController;
use App\Http\Controllers\HR\CursoController;
use App\Http\Controllers\HR\FolhaPagamentoController;
use App\Http\Controllers\HR\LocalTreinamentoController;
use App\Http\Controllers\HR\ProcessoSeletivoController;
use App\Http\Controllers\HR\ProcessoSeletivoDashboardController;
use App\Http\Controllers\HR\TreinamentoController;
use App\Http\Controllers\HR\TreinamentoDashboardController;
use App\Http\Controllers\HR\TreinamentoPresencaController;
use App\Http\Controllers\KanbanColunaController;
use App\Http\Controllers\MapaRiscoController;
use App\Http\Controllers\MissaoVisaoValoresController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NaoConformidadeController;
use App\Http\Controllers\NossaHistoriaController;
use App\Http\Controllers\ObjetivoQualidadeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlanoAcaoController;
use App\Http\Controllers\PoliticaQualidadeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\SwotAnalysisController;
use App\Http\Controllers\TarefaProjetoController;
use App\Http\Middleware\CheckMasterAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'company.required'])
    ->name('dashboard');

// Onboarding obrigatorio (autenticado mas SEM company ainda).
// Master admin bypassa o middleware company.required automaticamente.
// Ver memoria sgi-laravel-access-rules para a regra de negocio.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding/company', [OnboardingController::class, 'showCompanyForm'])
        ->name('onboarding.company');
    Route::post('/onboarding/company', [OnboardingController::class, 'completeOnboarding'])
        ->middleware('throttle:register')
        ->name('onboarding.complete');
    Route::post('/onboarding/lookup-cnpj', [OnboardingController::class, 'lookupCnpj'])
        ->middleware('throttle:cnpj-lookup')
        ->name('onboarding.lookup-cnpj');
    Route::get('/onboarding/pending', [OnboardingController::class, 'pending'])
        ->name('onboarding.pending');
});

// 'verified' exige email_verified_at != NULL. Novos usuários registrados
// via /register precisam clicar no link de verificação enviado por e-mail.
// Em dev/staging sem SMTP, considere setar email_verified_at via tinker
// ou desabilitar este middleware no .env local.
//
// 'company.required' adiciona um terceiro gate: usuario precisa ter
// concluido o onboarding (companies.cnpj cadastrado e users.company_id
// populado). Master admin bypassa. Sem isso, todas as queries via
// TenantScope retornam vazio.
Route::middleware(['auth', 'verified', 'company.required'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/hr/funcionarios/{funcionario}', [FuncionarioController::class, 'show'])->name('admin.funcionarios.show');
    Route::get('/hr/ferias', [FeriasController::class, 'index'])->name('admin.ferias.index');
    Route::get('/hr/ferias/{ferias}', [FeriasController::class, 'show'])->name('admin.ferias.show');

    // Novos Recursos do RH
    Route::get('/hr/dashboard', [App\Http\Controllers\HR\DashboardController::class, 'index'])->name('admin.hr.dashboard');

    // Estes cadastros usam formularios modais na tela index. Nao possuem
    // paginas dedicadas para create, show ou edit.
    Route::resource('/hr/areas', AreaController::class, ['as' => 'admin.hr'])
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/hr/cargos', CargoController::class, ['as' => 'admin.hr'])
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/hr/beneficios', BeneficioController::class, ['as' => 'admin.hr'])
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/hr/folha-pagamento', FolhaPagamentoController::class, ['as' => 'admin.hr'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::post('/notifications/{id}/read', function ($id) {
        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();

        return back();
    })->name('notifications.read');

    // Módulo STS - Auditorias
    Route::resource('auditorias', AuditoriaController::class);

    // Módulo STS - Planos de Ação
    Route::resource('planos-acao', PlanoAcaoController::class)->parameters([
        'planos-acao' => 'id',
    ]);

    // Módulo STS - Não Conformidades (FMQUA01)
    Route::get('nao-conformidades/{naoConformidade}/evidencias/{index}', [NaoConformidadeController::class, 'evidencia'])
        ->whereNumber('index')
        ->name('nao-conformidades.evidencias.show');
    Route::resource('nao-conformidades', NaoConformidadeController::class)->parameters([
        'nao-conformidades' => 'id',
    ]);

    // Modulo Projetos e Tarefas — RESTRITO a Master Admin (regra de
    // negocio: ver memoria sgi-laravel-access-rules item 3).
    // Outros usuarios nao acessam estas rotas mesmo com a URL direta.
    Route::middleware(CheckMasterAdmin::class)->group(function () {
        Route::resource('projetos', ProjetoController::class);
        Route::get('projetos/{projeto}/gantt', [ProjetoController::class, 'gantt'])->name('projetos.gantt');
        Route::post('projetos/{projeto}/gantt/save', [ProjetoController::class, 'saveGantt'])->name('projetos.gantt.save');

        Route::resource('kanban-colunas', KanbanColunaController::class)->only(['store', 'update', 'destroy']);
        Route::post('kanban-colunas/reorder', [KanbanColunaController::class, 'reorder'])->name('kanban-colunas.reorder');

        Route::post('tarefas/reorder', [TarefaProjetoController::class, 'reorder'])->name('tarefas.reorder');
        Route::apiResource('beneficios', BeneficioController::class)->except(['show']);

        // Treinamentos Module
        Route::apiResource('treinamentos-cursos', CursoController::class)
            ->except(['show'])
            ->parameters(['treinamentos-cursos' => 'curso']);
        Route::apiResource('treinamentos-locais', LocalTreinamentoController::class)
            ->except(['show'])
            ->parameters(['treinamentos-locais' => 'localTreinamento']);
        Route::apiResource('treinamentos-turmas', TreinamentoController::class)
            ->except(['show'])
            ->parameters(['treinamentos-turmas' => 'treinamento']);
        Route::get('treinamentos-dashboard', [TreinamentoDashboardController::class, 'index'])->name('treinamentos-dashboard.index');

        // Treinamento Lista Presença
        Route::get('treinamentos-turmas/{treinamento}/presenca', [TreinamentoPresencaController::class, 'show'])->name('treinamentos-presenca.show');
        Route::post('treinamentos-turmas/{treinamento}/presenca/add', [TreinamentoPresencaController::class, 'addAluno'])->name('treinamentos-presenca.add');
        Route::post('treinamentos-turmas/{treinamento}/presenca/remove', [TreinamentoPresencaController::class, 'removeAluno'])->name('treinamentos-presenca.remove');
        Route::post('treinamentos-turmas/{treinamento}/presenca/status', [TreinamentoPresencaController::class, 'updateStatus'])->name('treinamentos-presenca.status');

        // Processos Seletivos Module
        Route::resource('processos-seletivos', ProcessoSeletivoController::class)
            ->except(['create', 'edit']);
        Route::resource('candidatos', CandidatoController::class)->only(['store', 'update', 'destroy']);
        Route::get('processos-seletivos-dashboard', [ProcessoSeletivoDashboardController::class, 'index'])->name('processos-seletivos-dashboard.index');

        Route::resource('tarefas', TarefaProjetoController::class)->only(['store', 'update', 'destroy']);
        Route::patch('tarefas/{tarefa}/gantt', [TarefaProjetoController::class, 'updateGantt'])->name('tarefas.gantt.update');

        // Tarefa Details Routes
        Route::post('tarefas/{tarefa}/comentarios', [TarefaProjetoController::class, 'storeComment'])->name('tarefas.comentarios.store');
        Route::delete('tarefas/comentarios/{comentario}', [TarefaProjetoController::class, 'deleteComment'])->name('tarefas.comentarios.destroy');

        Route::post('tarefas/{tarefa}/anexos', [TarefaProjetoController::class, 'storeAttachment'])->name('tarefas.anexos.store');
        Route::get('tarefas/anexos/{anexo}/download', [TarefaProjetoController::class, 'downloadAttachment'])->name('tarefas.anexos.download');
        Route::delete('tarefas/anexos/{anexo}', [TarefaProjetoController::class, 'deleteAttachment'])->name('tarefas.anexos.destroy');

        Route::post('tarefas/{tarefa}/checklists', [TarefaProjetoController::class, 'storeChecklist'])->name('tarefas.checklists.store');
        Route::put('tarefas/checklists/{checklist}', [TarefaProjetoController::class, 'updateChecklist'])->name('tarefas.checklists.update');
        Route::delete('tarefas/checklists/{checklist}', [TarefaProjetoController::class, 'deleteChecklist'])->name('tarefas.checklists.destroy');
    });

    // Módulo ISO 9001 - Política da Qualidade
    Route::get('politica-qualidade', [PoliticaQualidadeController::class, 'index'])->name('politica-qualidade.index');
    Route::post('politica-qualidade/salvar-rascunho', [PoliticaQualidadeController::class, 'salvarRascunho'])->name('politica-qualidade.salvar-rascunho');
    Route::post('politica-qualidade/enviar-revisao', [PoliticaQualidadeController::class, 'enviarRevisao'])->name('politica-qualidade.enviar-revisao');
    Route::post('politica-qualidade/aprovar-revisao', [PoliticaQualidadeController::class, 'aprovarRevisao'])->name('politica-qualidade.aprovar-revisao');
    Route::post('politica-qualidade/aprovar-final', [PoliticaQualidadeController::class, 'aprovarFinal'])->name('politica-qualidade.aprovar-final');
    Route::post('politica-qualidade/devolver', [PoliticaQualidadeController::class, 'devolver'])->name('politica-qualidade.devolver');
    Route::get('politica-qualidade/pdf', [PoliticaQualidadeController::class, 'gerarPdf'])->name('politica-qualidade.pdf');

    // Módulo ISO 9001 - Escopo do SGI
    Route::get('escopo', [EscopoController::class, 'index'])->name('escopo.index');
    Route::post('escopo/salvar-rascunho', [EscopoController::class, 'salvarRascunho'])->name('escopo.salvar-rascunho');
    Route::post('escopo/enviar-revisao', [EscopoController::class, 'enviarRevisao'])->name('escopo.enviar-revisao');
    Route::post('escopo/aprovar-revisao', [EscopoController::class, 'aprovarRevisao'])->name('escopo.aprovar-revisao');
    Route::post('escopo/aprovar-final', [EscopoController::class, 'aprovarFinal'])->name('escopo.aprovar-final');
    Route::post('escopo/devolver', [EscopoController::class, 'devolver'])->name('escopo.devolver');
    Route::get('escopo/pdf', [EscopoController::class, 'gerarPdf'])->name('escopo.pdf');

    // Módulo ISO 9001 - Objetivos da Qualidade
    Route::resource('objetivos-qualidade', ObjetivoQualidadeController::class);
    Route::post('objetivos-qualidade/{id}/enviar-revisao', [ObjetivoQualidadeController::class, 'enviarRevisao'])->name('objetivos-qualidade.enviar-revisao');
    Route::post('objetivos-qualidade/{id}/aprovar-revisao', [ObjetivoQualidadeController::class, 'aprovarRevisao'])->name('objetivos-qualidade.aprovar-revisao');
    Route::post('objetivos-qualidade/{id}/aprovar-final', [ObjetivoQualidadeController::class, 'aprovarFinal'])->name('objetivos-qualidade.aprovar-final');
    Route::post('objetivos-qualidade/{id}/devolver', [ObjetivoQualidadeController::class, 'devolver'])->name('objetivos-qualidade.devolver');
    Route::get('objetivos-qualidade/{id}/pdf', [ObjetivoQualidadeController::class, 'gerarPdf'])->name('objetivos-qualidade.pdf');

    // Módulo ISO 9001 - Nossa História
    Route::get('nossa-historia', [NossaHistoriaController::class, 'index'])->name('nossa-historia.index');
    Route::post('nossa-historia/salvar', [NossaHistoriaController::class, 'salvar'])->name('nossa-historia.salvar');

    // Módulo ISO 9001 - Missão, Visão e Valores
    Route::get('missao-visao-valores', [MissaoVisaoValoresController::class, 'index'])->name('missao-visao-valores.index');
    Route::post('missao-visao-valores/salvar-rascunho', [MissaoVisaoValoresController::class, 'salvarRascunho'])->name('missao-visao-valores.salvar-rascunho');
    Route::post('missao-visao-valores/enviar-revisao', [MissaoVisaoValoresController::class, 'enviarRevisao'])->name('missao-visao-valores.enviar-revisao');
    Route::post('missao-visao-valores/aprovar-revisao', [MissaoVisaoValoresController::class, 'aprovarRevisao'])->name('missao-visao-valores.aprovar-revisao');
    Route::post('missao-visao-valores/aprovar-final', [MissaoVisaoValoresController::class, 'aprovarFinal'])->name('missao-visao-valores.aprovar-final');
    Route::post('missao-visao-valores/devolver', [MissaoVisaoValoresController::class, 'devolver'])->name('missao-visao-valores.devolver');
    Route::get('missao-visao-valores/pdf', [MissaoVisaoValoresController::class, 'exportarPdf'])->name('missao-visao-valores.pdf');

    // Módulo ISO 9001 - Controle de Documentos
    Route::resource('controle-documentos', DocumentoRegistroController::class)->except(['create', 'show', 'edit']);
    Route::post('controle-documentos/{id}/revisoes', [DocumentoRegistroController::class, 'storeRevisao'])->name('controle-documentos.revisoes.store');
    Route::delete('controle-documentos/{id}/revisoes/{revisaoId}', [DocumentoRegistroController::class, 'destroyRevisao'])->name('controle-documentos.revisoes.destroy');

    // Módulo ISO 9001 - Atas de Reunião
    Route::resource('atas-reuniao', AtaReuniaoController::class)
        ->parameters(['atas-reuniao' => 'ata']);
    Route::post('atas-reuniao/{ata}/solicitar-assinaturas', [AtaReuniaoController::class, 'solicitarAssinaturas'])->name('atas-reuniao.solicitar-assinaturas');
    Route::post('atas-reuniao/{ata}/assinar', [AtaReuniaoController::class, 'assinar'])->name('atas-reuniao.assinar');
    Route::get('atas-reuniao/{ata}/pdf', [AtaReuniaoController::class, 'exportarPdf'])->name('atas-reuniao.pdf');

    // Módulo ISO 9001 - Controle de Calibrações
    Route::resource('controle-calibracoes', ControleCalibracaoController::class)->except(['show']);
    Route::get('controle-calibracoes/{controleCalibraco}/download', [ControleCalibracaoController::class, 'downloadArquivo'])->name('controle-calibracoes.download');
    Route::get('controle-calibracoes-exportar/pdf', [ControleCalibracaoController::class, 'exportarPdf'])->name('controle-calibracoes.pdf');

    // Módulo ISO 9001 - Gestão de Fornecedores
    Route::resource('fornecedores', FornecedorController::class);
    Route::post('fornecedores/criterios-padrao/{company}', [FornecedorController::class, 'saveCriteriosPadrao'])->name('fornecedor.criterios.padrao');

    // Sub-rotas para Documentos do Fornecedor
    Route::post('fornecedores/{fornecedor}/documentos', [FornecedorDocumentoController::class, 'store'])->name('fornecedor.documentos.store');
    Route::get('fornecedor-documentos/{documento}/download', [FornecedorDocumentoController::class, 'download'])->name('fornecedor.documentos.download');
    Route::post('fornecedor-documentos/{documento}/avaliar', [FornecedorDocumentoController::class, 'avaliar'])->name('fornecedor.documentos.avaliar');
    Route::delete('fornecedor-documentos/{documento}', [FornecedorDocumentoController::class, 'destroy'])->name('fornecedor.documentos.destroy');

    // Sub-rotas para Avaliações do Fornecedor
    Route::post('fornecedores/{fornecedor}/avaliacoes', [FornecedorAvaliacaoController::class, 'store'])->name('fornecedor.avaliacoes.store');

    // Módulo ISO 9001 - Mapa de Risco
    Route::resource('mapas-risco', MapaRiscoController::class);
    Route::post('mapas-risco/{mapas_risco}/enviar-aprovacao', [MapaRiscoController::class, 'enviarAprovacao'])->name('mapas-risco.enviar-aprovacao');
    Route::post('mapas-risco/{mapas_risco}/aprovar', [MapaRiscoController::class, 'aprovar'])->name('mapas-risco.aprovar');
    Route::post('mapas-risco/{mapas_risco}/rejeitar', [MapaRiscoController::class, 'rejeitar'])->name('mapas-risco.rejeitar');

    // Módulo ISO 9001 - Análise SWOT
    Route::resource('analise-swot', SwotAnalysisController::class);
    Route::post('analise-swot/{analise_swot}/enviar-aprovacao', [SwotAnalysisController::class, 'enviarAprovacao'])->name('analise-swot.enviar-aprovacao');
    Route::post('analise-swot/{analise_swot}/aprovar', [SwotAnalysisController::class, 'aprovar'])->name('analise-swot.aprovar');
    Route::post('analise-swot/{analise_swot}/rejeitar', [SwotAnalysisController::class, 'rejeitar'])->name('analise-swot.rejeitar');
});

// Admin Routes — Master Admin Only:
//   - Gerenciar todas as empresas (CRUD)
//   - Gerenciar todos os modulos dinamicos (Module CRUD)
//   - Módulo de RH (Funcionários e Férias)
// Estas rotas exigem CheckMasterAdmin. Nao aplica 'company.required'
// porque master nao precisa de company_id por design.
Route::middleware(['auth', 'verified', CheckMasterAdmin::class])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('company-registrations', [CompanyController::class, 'registrations'])
            ->name('company-registrations.index');
        Route::get('company-registrations/{company}', [CompanyController::class, 'showRegistration'])
            ->name('company-registrations.show');
        Route::post('company-registrations/{company}/approve', [CompanyController::class, 'approveRegistration'])
            ->middleware('throttle:10,1')
            ->name('company-registrations.approve');
        Route::post('company-registrations/{company}/reject', [CompanyController::class, 'rejectRegistration'])
            ->middleware('throttle:10,1')
            ->name('company-registrations.reject');
        // A interface usa a listagem como leitura e nao possui telas show.
        // Nao registre endpoints que apontariam para metodos inexistentes.
        Route::resource('companies', CompanyController::class)->except(['show']);
        Route::resource('modules', ModuleController::class)->except(['show']);

        // Módulo de RH e Controle de Férias
        Route::get('ferias/mapa', FeriasController::class.'@mapa')->name('ferias.mapa');
        Route::resource('funcionarios', FuncionarioController::class)->except(['create', 'edit']);
        Route::resource('ferias', FeriasController::class)->except(['create', 'edit']);
    });

// Admin/Users — acessivel para Administrador da empresa E master admin.
// O proprio UserController:
//   - Filtra lista por company_id quando non-master
//   - Restringe roles atribuiveis as 5 do negocio
//   - Bloqueia gestao de usuarios de outras empresas
//   - Bloqueia gestao de master admins por non-master
// Aplicacao do CheckMasterAdmin foi REMOVIDA aqui propositalmente
// porque a regra de acesso e mais sutil (master vs admin da empresa).
Route::middleware(['auth', 'verified', 'company.required'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

// ─────────────────────────────────────────────────────────────────────────────
// GERENCIADOR DE ARQUIVOS
// Visível apenas para master_admin (sidebar) ou empresas com acesso liberado.
// Isolamento: cada usuário só acessa arquivos de sua company_id.
// Master admin acessa qualquer empresa via ?company_id=X.
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('file-manager')->name('file-manager.')->middleware(['auth', 'verified'])->group(function () {

    // Visualização principal
    Route::get('/', [FileManagerController::class, 'index'])->name('index');

    // Upload
    Route::post('/upload', [FileManagerController::class, 'upload'])->name('upload');

    // Download
    Route::get('/download/{id}', [FileManagerController::class, 'download'])->name('download');

    // Pastas
    Route::post('/pastas', [FileManagerController::class, 'storeFolder'])->name('pastas.store');
    Route::delete('/pastas/{id}', [FileManagerController::class, 'destroyFolder'])->name('pastas.destroy');
    Route::patch('/pastas/{id}/restore', [FileManagerController::class, 'restoreFolder'])->name('pastas.restore');

    // Arquivos
    Route::delete('/arquivos/{id}', [FileManagerController::class, 'destroyFile'])->name('arquivos.destroy');
    Route::patch('/arquivos/{id}/star', [FileManagerController::class, 'toggleStar'])->name('arquivos.star');
    Route::patch('/arquivos/{id}/restore', [FileManagerController::class, 'restoreFile'])->name('arquivos.restore');
    Route::delete('/arquivos/{id}/force', [FileManagerController::class, 'forceDeleteFile'])->name('arquivos.force-delete');

    // Acesso por empresa (somente master_admin)
    Route::get('/empresa-acesso', [FileManagerController::class, 'empresaAcesso'])->name('empresa-acesso.index');
    Route::post('/empresa-acesso/{companyId}/toggle', [FileManagerController::class, 'toggleEmpresaAcesso'])->name('empresa-acesso.toggle');

    // Grupos de acesso
    Route::get('/grupos', [FileManagerController::class, 'grupos'])->name('grupos.index');
    Route::post('/grupos', [FileManagerController::class, 'storeGrupo'])->name('grupos.store');
    Route::delete('/grupos/{id}', [FileManagerController::class, 'destroyGrupo'])->name('grupos.destroy');
    Route::post('/grupos/{grupoId}/users', [FileManagerController::class, 'addUserToGrupo'])->name('grupos.users.add');
    Route::delete('/grupos/{grupoId}/users/{userId}', [FileManagerController::class, 'removeUserFromGrupo'])->name('grupos.users.remove');
    Route::post('/grupos/{grupoId}/permissoes', [FileManagerController::class, 'setPastaPermission'])->name('grupos.permissoes');
});

require __DIR__.'/auth.php';
