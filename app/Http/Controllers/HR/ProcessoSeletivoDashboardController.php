<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\ProcessoSeletivo;
use App\Models\Candidato;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessoSeletivoDashboardController extends Controller
{
    public function index(Request $request)
    {
        $processos = ProcessoSeletivo::all();
        $candidatos = Candidato::all();

        $kpis = [
            'total_processos' => $processos->count(),
            'processos_abertos' => $processos->where('status', 'Em Andamento')->count(),
            'custo_planejado' => $processos->sum('custo_planejado'),
            'custo_realizado' => $processos->sum('custo_realizado'),
            'total_candidatos' => $candidatos->count(),
        ];

        // Funil de Etapas
        $funil = Candidato::selectRaw('etapa_atual, count(*) as total')
            ->groupBy('etapa_atual')
            ->pluck('total', 'etapa_atual')
            ->toArray();

        return Inertia::render('HR/ProcessosSeletivos/Dashboard', [
            'kpis' => $kpis,
            'funil' => $funil,
        ]);
    }
}
