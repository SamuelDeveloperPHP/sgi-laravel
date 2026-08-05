<?php

namespace App\Http\Controllers;

use App\Models\Ferias;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FeriasController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ferias::class);
        $query = Ferias::with(['funcionario.company', 'company']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('funcionario', function($q) use ($search) {
                $q->where('nome', 'LIKE', "%{$search}%")
                  ->orWhere('matricula', 'LIKE', "%{$search}%")
                  ->orWhere('cpf', 'LIKE', "%{$search}%");
            });
        }

        $ferias = $query->latest()->paginate(15)->withQueryString();
        
        // We only need basic data for the dropdown
        $funcionarios = Funcionario::with('company:id,razao_social,nome_fantasia')
            ->select('id', 'nome', 'company_id')
            ->orderBy('nome')
            ->get();

        return inertia('HR/Ferias/Index', [
            'ferias' => $ferias,
            'funcionarios' => $funcionarios,
            'filters' => $request->only(['company_id', 'search'])
        ]);
    }

    public function mapa(Request $request)
    {
        $this->authorize('viewAny', Ferias::class);
        // Get all ferias for the current year or requested year
        $year = $request->input('year', date('Y'));
        
        $query = Ferias::with(['funcionario.company', 'company'])
            ->where(function($q) use ($year) {
                $q->whereYear('gozo_1_inicio', $year)
                  ->orWhereYear('gozo_2_inicio', $year)
                  ->orWhereYear('gozo_3_inicio', $year);
            });

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $ferias = $query->orderBy('gozo_1_inicio')->get();
        $companies = \App\Models\Company::select('id', 'razao_social')->get();

        return inertia('HR/Ferias/Mapa', [
            'ferias' => $ferias,
            'companies' => $companies,
            'filters' => ['year' => $year, 'company_id' => $request->company_id]
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ferias::class);
        $validated = $request->validate([
            'funcionario_id' => ['required', $this->funcionarioExistsRule($request)],
            'periodo_aquisitivo_inicio' => 'nullable|date',
            'periodo_aquisitivo_fim' => 'nullable|date|after_or_equal:periodo_aquisitivo_inicio',
            'dias_direito' => 'required|integer|min:1|max:30',
            'opcao_abono' => 'required|boolean',
            'dias_abono' => 'required|integer|min:0|max:10',
            'gozo_1_inicio' => 'nullable|date',
            'gozo_1_fim' => 'nullable|date|after_or_equal:gozo_1_inicio',
            'gozo_2_inicio' => 'nullable|date',
            'gozo_2_fim' => 'nullable|date|after_or_equal:gozo_2_inicio',
            'gozo_3_inicio' => 'nullable|date',
            'gozo_3_fim' => 'nullable|date|after_or_equal:gozo_3_inicio',
            'faltas' => 'required|integer|min:0',
            'valor_proventos' => 'nullable|numeric',
            'valor_1_3' => 'nullable|numeric',
            'valor_1_3_abono' => 'nullable|numeric',
            'desconto_inss' => 'nullable|numeric',
            'desconto_irpf' => 'nullable|numeric',
            'valor_liquido' => 'nullable|numeric',
            'status' => 'required|string|in:Programada,Em Gozo,Concluída,Cancelada'
        ]);

        DB::beginTransaction();
        try {
            $funcionario = Funcionario::findOrFail($validated['funcionario_id']);
            $validated['company_id'] = $funcionario->company_id;
            
            Ferias::create($validated);
            DB::commit();
            return redirect()->back()->with('message', 'Férias programadas com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao programar férias: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao programar férias.');
        }
    }

    public function show(Ferias $feria)
    {
        $this->authorize('view', $feria);
        $feria->load('funcionario.company');

        return inertia('HR/Ferias/Show', [
            'feria' => $feria
        ]);
    }

    public function update(Request $request, Ferias $feria)
    {
        $this->authorize('update', $feria);
        $validated = $request->validate([
            'funcionario_id' => ['required', $this->funcionarioExistsRule($request)],
            'periodo_aquisitivo_inicio' => 'nullable|date',
            'periodo_aquisitivo_fim' => 'nullable|date|after_or_equal:periodo_aquisitivo_inicio',
            'dias_direito' => 'required|integer|min:1|max:30',
            'opcao_abono' => 'required|boolean',
            'dias_abono' => 'required|integer|min:0|max:10',
            'gozo_1_inicio' => 'nullable|date',
            'gozo_1_fim' => 'nullable|date|after_or_equal:gozo_1_inicio',
            'gozo_2_inicio' => 'nullable|date',
            'gozo_2_fim' => 'nullable|date|after_or_equal:gozo_2_inicio',
            'gozo_3_inicio' => 'nullable|date',
            'gozo_3_fim' => 'nullable|date|after_or_equal:gozo_3_inicio',
            'faltas' => 'required|integer|min:0',
            'valor_proventos' => 'nullable|numeric',
            'valor_1_3' => 'nullable|numeric',
            'valor_1_3_abono' => 'nullable|numeric',
            'desconto_inss' => 'nullable|numeric',
            'desconto_irpf' => 'nullable|numeric',
            'valor_liquido' => 'nullable|numeric',
            'status' => 'required|string|in:Programada,Em Gozo,Concluída,Cancelada'
        ]);

        DB::beginTransaction();
        try {
            $funcionario = Funcionario::findOrFail($validated['funcionario_id']);
            $validated['company_id'] = $funcionario->company_id;

            $feria->update($validated);
            DB::commit();
            return redirect()->back()->with('message', 'Férias atualizadas com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar férias: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar férias.');
        }
    }

    public function destroy(Ferias $feria)
    {
        $this->authorize('delete', $feria);
        DB::beginTransaction();
        try {
            $feria->delete();
            DB::commit();
            return redirect()->back()->with('message', 'Férias removidas com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao remover férias: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao remover férias.');
        }
    }

    private function funcionarioExistsRule(Request $request)
    {
        $rule = Rule::exists('rh_funcionarios', 'id');

        if (!$request->user()->is_master_admin) {
            $rule->where('company_id', $request->user()->company_id);
        }

        return $rule;
    }
}
