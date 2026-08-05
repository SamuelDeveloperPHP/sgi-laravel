<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Treinamento;
use App\Models\Curso;
use App\Models\TreinamentoPresenca;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreinamentoRelatorioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Treinamento::class);

        $query = clone Treinamento::query()->with(['curso', 'local'])->withCount(['presencas as presenças_confirmadas' => function($q) {
            $q->where('presente', true);
        }]);

        if ($request->filled('curso_id')) {
            $query->where('curso_id', $request->curso_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data_inicio', [$request->data_inicio, $request->data_fim]);
        }

        $resultados = $request->has('filtrar') ? $query->orderByDesc('data_inicio')->get() : [];

        return Inertia::render('HR/Treinamentos/Relatorios', [
            'cursos' => Curso::orderBy('nome')->get(),
            'resultados' => $resultados,
            'filtros' => $request->all()
        ]);
    }
}
