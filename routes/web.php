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
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// 'verified' exige email_verified_at != NULL. Novos usuários registrados
// via /register precisam clicar no link de verificação enviado por e-mail.
// Em dev/staging sem SMTP, considere setar email_verified_at via tinker
// ou desabilitar este middleware no .env local.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
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

    // Módulo STS - Projetos e Tarefas
    Route::resource('projetos', \App\Http\Controllers\ProjetoController::class);
    
    Route::resource('kanban-colunas', \App\Http\Controllers\KanbanColunaController::class)->only(['store', 'update', 'destroy']);
    Route::post('kanban-colunas/reorder', [\App\Http\Controllers\KanbanColunaController::class, 'reorder'])->name('kanban-colunas.reorder');
    
    Route::post('tarefas/reorder', [\App\Http\Controllers\TarefaProjetoController::class, 'reorder'])->name('tarefas.reorder');
    Route::resource('tarefas', \App\Http\Controllers\TarefaProjetoController::class)->only(['store', 'update', 'destroy']);
});

// Admin Routes (Master Admin Only)
Route::middleware(['auth', 'verified', \App\Http\Middleware\CheckMasterAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class);
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';
