<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaInterna;
use App\Models\NaoConformidade;
use App\Models\PlanoAcao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the SGI Dashboard.
     */
    public function index()
    {
        // Resumo estatístico
        $stats = [
            'total_pa' => PlanoAcao::count(),
            'total_nc' => NaoConformidade::count(),
            'total_auditorias' => AuditoriaInterna::count(),
        ];

        // Últimas Não Conformidades
        $recent_nc = NaoConformidade::orderBy('created', 'desc')
            ->take(5)
            ->get()
            ->map(function ($nc) {
                return [
                    'id' => $nc->id,
                    'descricao' => str($nc->descOcorrencia)->limit(50),
                    'data' => $nc->dataAbertura ? date('d/m/Y', strtotime($nc->dataAbertura)) : 'N/A',
                    'setor' => $nc->setorAbertura ?? 'Não definido',
                    'empresa' => $nc->empresaAbertura ?? 'Não definida'
                ];
            });

        // Últimos Planos de Ação
        $recent_pa = PlanoAcao::orderBy('created', 'desc')
            ->take(5)
            ->get()
            ->map(function ($pa) {
                return [
                    'id' => $pa->id,
                    'descricao' => str($pa->o_q_aconteceu)->limit(50),
                    'prazo' => $pa->dt_prazo ? date('d/m/Y', strtotime($pa->dt_prazo)) : 'N/A',
                    'status' => $pa->status ?? 'Aberto'
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_nc' => $recent_nc,
            'recent_pa' => $recent_pa
        ]);
    }
}
