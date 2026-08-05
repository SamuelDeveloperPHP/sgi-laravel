<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Funcionario::class);
        $query = Funcionario::with('company', 'area', 'cargo');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome', 'LIKE', "%{$search}%")
                  ->orWhere('matricula', 'LIKE', "%{$search}%")
                  ->orWhere('cpf', 'LIKE', "%{$search}%");
            });
        }

        $funcionarios = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::select('id', 'razao_social')->get();
        
        $areas = \App\Models\Area::all();
        $cargos = \App\Models\Cargo::all();

        return inertia('HR/Funcionarios/Index', [
            'funcionarios' => $funcionarios,
            'companies' => $companies,
            'areas' => $areas,
            'cargos' => $cargos,
            'filters' => $request->only(['company_id', 'search'])
        ]);
    }

    public function store(\App\Http\Requests\StoreFuncionarioRequest $request)
    {
        $this->authorize('create', Funcionario::class);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            Funcionario::create($validated);
            DB::commit();
            return redirect()->back()->with('message', 'Funcionário cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar funcionário: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao salvar funcionário.');
        }
    }

    public function show(Funcionario $funcionario)
    {
        $this->authorize('view', $funcionario);
        $funcionario->load(['company', 'area', 'cargo', 'ferias' => function($q) {
            $q->orderBy('periodo_aquisitivo_inicio', 'desc');
        }]);

        return inertia('HR/Funcionarios/Show', [
            'funcionario' => $funcionario
        ]);
    }

    public function update(\App\Http\Requests\UpdateFuncionarioRequest $request, Funcionario $funcionario)
    {
        $this->authorize('update', $funcionario);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $funcionario->update($validated);
            DB::commit();
            return redirect()->back()->with('message', 'Funcionário atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar funcionário: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar funcionário.');
        }
    }

    public function destroy(Funcionario $funcionario)
    {
        $this->authorize('delete', $funcionario);
        DB::beginTransaction();
        try {
            $funcionario->delete();
            DB::commit();
            return redirect()->back()->with('message', 'Funcionário removido com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao remover funcionário: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao remover funcionário.');
        }
    }
}
