<?php

use App\Http\Controllers\ProfileController;
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

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'company.required'])
    ->name('dashboard');

// Onboarding obrigatorio (autenticado mas SEM company ainda).
// Master admin bypassa o middleware company.required automaticamente.
// Ver memoria sgi-laravel-access-rules para a regra de negocio.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding/company', [\App\Http\Controllers\OnboardingController::class, 'showCompanyForm'])
        ->name('onboarding.company');
    Route::post('/onboarding/company', [\App\Http\Controllers\OnboardingController::class, 'completeOnboarding'])
        ->middleware('throttle:register')
        ->name('onboarding.complete');
    Route::post('/onboarding/lookup-cnpj', [\App\Http\Controllers\OnboardingController::class, 'lookupCnpj'])
        ->middleware('throttle:6,1') // 6 lookups/min para nao estourar a ReceitaWS
        ->name('onboarding.lookup-cnpj');
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

    Route::post('/notifications/{id}/read', function($id) {
        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();
        return back();
    })->name('notifications.read');
    
    // Módulo STS - Auditorias
    Route::resource('auditorias', \App\Http\Controllers\AuditoriaController::class);
    
    // Módulo STS - Planos de Ação
    Route::resource('planos-acao', \App\Http\Controllers\PlanoAcaoController::class)->parameters([
        'planos-acao' => 'id'
    ]);
    
    // Módulo STS - Não Conformidades (FMQUA01)
    Route::resource('nao-conformidades', \App\Http\Controllers\NaoConformidadeController::class)->parameters([
        'nao-conformidades' => 'id'
    ]);

    // Modulo Projetos e Tarefas — RESTRITO a Master Admin (regra de
    // negocio: ver memoria sgi-laravel-access-rules item 3).
    // Outros usuarios nao acessam estas rotas mesmo com a URL direta.
    Route::middleware(\App\Http\Middleware\CheckMasterAdmin::class)->group(function () {
        Route::resource('projetos', \App\Http\Controllers\ProjetoController::class);

        Route::resource('kanban-colunas', \App\Http\Controllers\KanbanColunaController::class)->only(['store', 'update', 'destroy']);
        Route::post('kanban-colunas/reorder', [\App\Http\Controllers\KanbanColunaController::class, 'reorder'])->name('kanban-colunas.reorder');

        Route::post('tarefas/reorder', [\App\Http\Controllers\TarefaProjetoController::class, 'reorder'])->name('tarefas.reorder');
        Route::resource('tarefas', \App\Http\Controllers\TarefaProjetoController::class)->only(['store', 'update', 'destroy']);

        // Tarefa Details Routes
        Route::post('tarefas/{tarefa}/comentarios', [\App\Http\Controllers\TarefaProjetoController::class, 'storeComment'])->name('tarefas.comentarios.store');
        Route::delete('tarefas/comentarios/{comentario}', [\App\Http\Controllers\TarefaProjetoController::class, 'deleteComment'])->name('tarefas.comentarios.destroy');

        Route::post('tarefas/{tarefa}/anexos', [\App\Http\Controllers\TarefaProjetoController::class, 'storeAttachment'])->name('tarefas.anexos.store');
        Route::delete('tarefas/anexos/{anexo}', [\App\Http\Controllers\TarefaProjetoController::class, 'deleteAttachment'])->name('tarefas.anexos.destroy');

        Route::post('tarefas/{tarefa}/checklists', [\App\Http\Controllers\TarefaProjetoController::class, 'storeChecklist'])->name('tarefas.checklists.store');
        Route::put('tarefas/checklists/{checklist}', [\App\Http\Controllers\TarefaProjetoController::class, 'updateChecklist'])->name('tarefas.checklists.update');
        Route::delete('tarefas/checklists/{checklist}', [\App\Http\Controllers\TarefaProjetoController::class, 'deleteChecklist'])->name('tarefas.checklists.destroy');
    });

    // Módulo ISO 9001 - Política da Qualidade
    Route::get('politica-qualidade', [\App\Http\Controllers\PoliticaQualidadeController::class, 'index'])->name('politica-qualidade.index');
    Route::post('politica-qualidade/salvar-rascunho', [\App\Http\Controllers\PoliticaQualidadeController::class, 'salvarRascunho'])->name('politica-qualidade.salvar-rascunho');
    Route::post('politica-qualidade/enviar-revisao', [\App\Http\Controllers\PoliticaQualidadeController::class, 'enviarRevisao'])->name('politica-qualidade.enviar-revisao');
    Route::post('politica-qualidade/aprovar-revisao', [\App\Http\Controllers\PoliticaQualidadeController::class, 'aprovarRevisao'])->name('politica-qualidade.aprovar-revisao');
    Route::post('politica-qualidade/aprovar-final', [\App\Http\Controllers\PoliticaQualidadeController::class, 'aprovarFinal'])->name('politica-qualidade.aprovar-final');
    Route::post('politica-qualidade/devolver', [\App\Http\Controllers\PoliticaQualidadeController::class, 'devolver'])->name('politica-qualidade.devolver');
    Route::get('politica-qualidade/pdf', [\App\Http\Controllers\PoliticaQualidadeController::class, 'gerarPdf'])->name('politica-qualidade.pdf');

    // Módulo ISO 9001 - Escopo do SGI
    Route::get('escopo', [\App\Http\Controllers\EscopoController::class, 'index'])->name('escopo.index');
    Route::post('escopo/salvar-rascunho', [\App\Http\Controllers\EscopoController::class, 'salvarRascunho'])->name('escopo.salvar-rascunho');
    Route::post('escopo/enviar-revisao', [\App\Http\Controllers\EscopoController::class, 'enviarRevisao'])->name('escopo.enviar-revisao');
    Route::post('escopo/aprovar-revisao', [\App\Http\Controllers\EscopoController::class, 'aprovarRevisao'])->name('escopo.aprovar-revisao');
    Route::post('escopo/aprovar-final', [\App\Http\Controllers\EscopoController::class, 'aprovarFinal'])->name('escopo.aprovar-final');
    Route::post('escopo/devolver', [\App\Http\Controllers\EscopoController::class, 'devolver'])->name('escopo.devolver');
    Route::get('escopo/pdf', [\App\Http\Controllers\EscopoController::class, 'gerarPdf'])->name('escopo.pdf');

    // Módulo ISO 9001 - Objetivos da Qualidade
    Route::resource('objetivos-qualidade', \App\Http\Controllers\ObjetivoQualidadeController::class);
    Route::post('objetivos-qualidade/{id}/enviar-revisao', [\App\Http\Controllers\ObjetivoQualidadeController::class, 'enviarRevisao'])->name('objetivos-qualidade.enviar-revisao');
    Route::post('objetivos-qualidade/{id}/aprovar-revisao', [\App\Http\Controllers\ObjetivoQualidadeController::class, 'aprovarRevisao'])->name('objetivos-qualidade.aprovar-revisao');
    Route::post('objetivos-qualidade/{id}/aprovar-final', [\App\Http\Controllers\ObjetivoQualidadeController::class, 'aprovarFinal'])->name('objetivos-qualidade.aprovar-final');
    Route::post('objetivos-qualidade/{id}/devolver', [\App\Http\Controllers\ObjetivoQualidadeController::class, 'devolver'])->name('objetivos-qualidade.devolver');
    Route::get('objetivos-qualidade/{id}/pdf', [\App\Http\Controllers\ObjetivoQualidadeController::class, 'gerarPdf'])->name('objetivos-qualidade.pdf');

    // Módulo ISO 9001 - Nossa História
    Route::get('nossa-historia', [\App\Http\Controllers\NossaHistoriaController::class, 'index'])->name('nossa-historia.index');
    Route::post('nossa-historia/salvar', [\App\Http\Controllers\NossaHistoriaController::class, 'salvar'])->name('nossa-historia.salvar');

    // Módulo ISO 9001 - Missão, Visão e Valores
    Route::get('missao-visao-valores', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'index'])->name('missao-visao-valores.index');
    Route::post('missao-visao-valores/salvar-rascunho', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'salvarRascunho'])->name('missao-visao-valores.salvar-rascunho');
    Route::post('missao-visao-valores/enviar-revisao', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'enviarRevisao'])->name('missao-visao-valores.enviar-revisao');
    Route::post('missao-visao-valores/aprovar-revisao', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'aprovarRevisao'])->name('missao-visao-valores.aprovar-revisao');
    Route::post('missao-visao-valores/aprovar-final', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'aprovarFinal'])->name('missao-visao-valores.aprovar-final');
    Route::post('missao-visao-valores/devolver', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'devolver'])->name('missao-visao-valores.devolver');
    Route::get('missao-visao-valores/pdf', [\App\Http\Controllers\MissaoVisaoValoresController::class, 'exportarPdf'])->name('missao-visao-valores.pdf');

    // Módulo ISO 9001 - Controle de Documentos
    Route::resource('controle-documentos', \App\Http\Controllers\DocumentoRegistroController::class)->except(['create', 'show', 'edit']);
    Route::post('controle-documentos/{id}/revisoes', [\App\Http\Controllers\DocumentoRegistroController::class, 'storeRevisao'])->name('controle-documentos.revisoes.store');
    Route::delete('controle-documentos/{id}/revisoes/{revisaoId}', [\App\Http\Controllers\DocumentoRegistroController::class, 'destroyRevisao'])->name('controle-documentos.revisoes.destroy');

    // Módulo ISO 9001 - Atas de Reunião
    Route::resource('atas-reuniao', \App\Http\Controllers\AtaReuniaoController::class);
    Route::post('atas-reuniao/{ata}/solicitar-assinaturas', [\App\Http\Controllers\AtaReuniaoController::class, 'solicitarAssinaturas'])->name('atas-reuniao.solicitar-assinaturas');
    Route::post('atas-reuniao/{ata}/assinar', [\App\Http\Controllers\AtaReuniaoController::class, 'assinar'])->name('atas-reuniao.assinar');
    Route::get('atas-reuniao/{ata}/pdf', [\App\Http\Controllers\AtaReuniaoController::class, 'exportarPdf'])->name('atas-reuniao.pdf');

    // Módulo ISO 9001 - Controle de Calibrações
    Route::resource('controle-calibracoes', \App\Http\Controllers\ControleCalibracaoController::class)->except(['show']);
    Route::get('controle-calibracoes/{controleCalibraco}/download', [\App\Http\Controllers\ControleCalibracaoController::class, 'downloadArquivo'])->name('controle-calibracoes.download');
    Route::get('controle-calibracoes-exportar/pdf', [\App\Http\Controllers\ControleCalibracaoController::class, 'exportarPdf'])->name('controle-calibracoes.pdf');

    // Módulo ISO 9001 - Gestão de Fornecedores
    Route::resource('fornecedores', \App\Http\Controllers\FornecedorController::class);
    Route::post('fornecedores/criterios-padrao/{company}', [\App\Http\Controllers\FornecedorController::class, 'saveCriteriosPadrao'])->name('fornecedor.criterios.padrao');
    
    // Sub-rotas para Documentos do Fornecedor
    Route::post('fornecedores/{fornecedor}/documentos', [\App\Http\Controllers\FornecedorDocumentoController::class, 'store'])->name('fornecedor.documentos.store');
    Route::get('fornecedor-documentos/{documento}/download', [\App\Http\Controllers\FornecedorDocumentoController::class, 'download'])->name('fornecedor.documentos.download');
    Route::post('fornecedor-documentos/{documento}/avaliar', [\App\Http\Controllers\FornecedorDocumentoController::class, 'avaliar'])->name('fornecedor.documentos.avaliar');
    Route::delete('fornecedor-documentos/{documento}', [\App\Http\Controllers\FornecedorDocumentoController::class, 'destroy'])->name('fornecedor.documentos.destroy');

    // Sub-rotas para Avaliações do Fornecedor
    Route::post('fornecedores/{fornecedor}/avaliacoes', [\App\Http\Controllers\FornecedorAvaliacaoController::class, 'store'])->name('fornecedor.avaliacoes.store');
});

// Admin Routes — Master Admin Only:
//   - Gerenciar todas as empresas (CRUD)
//   - Gerenciar todos os modulos dinamicos (Module CRUD)
// Estas rotas exigem CheckMasterAdmin. Nao aplica 'company.required'
// porque master nao precisa de company_id por design.
Route::middleware(['auth', 'verified', \App\Http\Middleware\CheckMasterAdmin::class])
    ->prefix('admin')->name('admin.')->group(function () {
    Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class);
    Route::resource('modules', \App\Http\Controllers\ModuleController::class)->except(['show']);
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
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';
