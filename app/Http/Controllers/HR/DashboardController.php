<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Funcionario;
use App\Models\FolhaPagamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Funcionario::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;

        // Estatísticas Gerais de Funcionários
        $totalFuncionarios = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))->count();
        $ativos = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))->whereIn('status', ['Ativo', 'Férias', 'Afastado'])->count();
        $inativos = $totalFuncionarios - $ativos;
        
        // Distribuição por Gênero
        $porGenero = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->select('genero', DB::raw('count(*) as total'))
            ->groupBy('genero')
            ->get();

        // Salário Médio e Massa Salarial (apenas ativos)
        $massaSalarial = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Ativo', 'Férias'])
            ->sum('salario_bruto');

        $salarioMedio = $ativos > 0 ? $massaSalarial / $ativos : 0;

        // Evolução da Folha (Últimos 6 meses)
        $evolucaoFolha = FolhaPagamento::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->select('competencia', DB::raw('SUM(custo_total) as total_custo'), DB::raw('SUM(salario_liquido) as total_liquido'))
            ->groupBy('competencia')
            ->orderBy('competencia', 'asc')
            ->limit(6)
            ->get();

        // Contratações e Demissões (Turnover) no ano atual
        $anoAtual = date('Y');
        $contratacoes = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereYear('data_admissao', $anoAtual)
            ->count();
        $demissoes = Funcionario::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereYear('data_demissao', $anoAtual)
            ->count();

        return Inertia::render('HR/Dashboard', [
            'stats' => [
                'totalFuncionarios' => $totalFuncionarios,
                'ativos' => $ativos,
                'inativos' => $inativos,
                'massaSalarialAtual' => $massaSalarial,
                'salarioMedio' => $salarioMedio,
                'turnover' => [
                    'ano' => $anoAtual,
                    'admissoes' => $contratacoes,
                    'demissoes' => $demissoes,
                ],
                'genero' => $porGenero,
                'evolucaoFolha' => $evolucaoFolha,
            ]
        ]);
    }
}
