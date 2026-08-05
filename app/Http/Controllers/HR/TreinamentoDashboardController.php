<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Treinamento;
use App\Models\TreinamentoMeta;
use App\Models\TreinamentoPresenca;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TreinamentoDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Treinamento::class);
        $companyId = getPermissionsTeamId(); // Or however tenant is retrieved, but we can just use auth()->user()->company_id
        $companyId = auth()->user()->company_id ?? getPermissionsTeamId();

        $ano = $request->get('ano', date('Y'));

        // Meta do ano
        $meta = TreinamentoMeta::where('ano', $ano)->first();
        $metaHoras = $meta ? $meta->meta_horas_treinamento : 0;

        // Horas realizadas no ano
        // A lógica de horas realizadas é a soma da (carga_horaria do curso) * (número de presenças confirmadas) daquele treinamento
        // Ou simplesmente as horas oferecidas (carga_horaria) nos treinamentos concluídos. Vamos adotar horas oferecidas para a empresa.
        $treinamentosConcluidos = Treinamento::with('curso')
            ->whereYear('data_inicio', $ano)
            ->where('status', 'Concluído')
            ->get();

        $horasRealizadas = 0;
        foreach ($treinamentosConcluidos as $t) {
            if ($t->curso) {
                $horasRealizadas += $t->curso->carga_horaria;
            }
        }

        // Gráfico de horas por mês
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $mes = Carbon::create($ano, $i, 1)->translatedFormat('M');
            $horasMes = 0;
            foreach ($treinamentosConcluidos as $t) {
                if (Carbon::parse($t->data_inicio)->month == $i && $t->curso) {
                    $horasMes += $t->curso->carga_horaria;
                }
            }
            $chartData[] = [
                'name' => ucfirst($mes),
                'horas' => $horasMes
            ];
        }

        // Top 5 cursos mais realizados (por presenças confirmadas)
        $topCursos = TreinamentoPresenca::where('presente', true)
            ->join('treinamentos', 'treinamentos.id', '=', 'treinamento_presencas.treinamento_id')
            ->join('cursos', 'cursos.id', '=', 'treinamentos.curso_id')
            ->whereYear('treinamentos.data_inicio', $ano)
            ->selectRaw('cursos.nome, count(treinamento_presencas.id) as total')
            ->groupBy('cursos.nome')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('HR/Treinamentos/Dashboard', [
            'ano' => $ano,
            'metaHoras' => $metaHoras,
            'horasRealizadas' => $horasRealizadas,
            'chartData' => $chartData,
            'topCursos' => $topCursos
        ]);
    }
}
