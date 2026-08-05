<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\FolhaPagamento;
use App\Models\Funcionario;
use App\Models\Company;
use App\Http\Requests\StoreFolhaPagamentoRequest;
use App\Http\Requests\UpdateFolhaPagamentoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FolhaPagamentoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', FolhaPagamento::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;
        
        $folhas = FolhaPagamento::with('funcionario:id,nome,cpf,matricula')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($request->competencia, function ($query, $competencia) {
                $query->where('competencia', $competencia);
            })
            ->orderBy('competencia', 'desc')
            ->paginate(15);

        $companies = auth()->user()->is_master_admin ? Company::all() : [];

        return Inertia::render('HR/FolhaPagamento/Index', [
            'folhas' => $folhas,
            'companies' => $companies,
            'filters' => $request->only(['competencia', 'company_id']),
        ]);
    }

    public function store(StoreFolhaPagamentoRequest $request)
    {
        $this->authorize('create', FolhaPagamento::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;
        $competencia = $request->competencia;

        // Recupera todos os funcionários ativos
        $funcionarios = Funcionario::where('company_id', $companyId)
            ->whereIn('status', ['Ativo', 'Férias'])
            ->get();

        $count = 0;

        foreach ($funcionarios as $funcionario) {
            // Verifica se já existe
            $existe = FolhaPagamento::where('funcionario_id', $funcionario->id)
                ->where('competencia', $competencia)
                ->exists();

            if (!$existe) {
                // Lógica simplificada de cálculo.
                // Na prática isso poderia vir da tabela pivot rh_funcionario_beneficio
                $salario = $funcionario->salario_bruto ?: ($funcionario->cargo ? $funcionario->cargo->salario_base : 1621);
                
                FolhaPagamento::create([
                    'company_id' => $companyId,
                    'funcionario_id' => $funcionario->id,
                    'competencia' => $competencia,
                    'salario_base' => $salario,
                    'total_proventos' => $salario,
                    'total_descontos' => 0, // Calcular INSS/IRRF aqui ou manualmente
                    'total_beneficios' => 0, // Somar beneficios da pivot
                    'salario_liquido' => $salario,
                    'custo_total' => $salario, // Salario + Beneficios + Encargos
                    'status' => 'Fechado',
                    'created_by' => auth()->id(),
                ]);
                $count++;
            }
        }

        return redirect()->route('admin.hr.folha-pagamento.index')
            ->with('success', "Folha da competência {$competencia} fechada para {$count} funcionários.");
    }

    public function update(UpdateFolhaPagamentoRequest $request, FolhaPagamento $folhaPagamento)
    {
        $this->authorize('update', $folhaPagamento);
        $liquido = $request->total_proventos - $request->total_descontos;
        $custo = $request->total_proventos + $request->total_beneficios;

        $folhaPagamento->update([
            'total_proventos' => $request->total_proventos,
            'total_descontos' => $request->total_descontos,
            'total_beneficios' => $request->total_beneficios,
            'salario_liquido' => $liquido,
            'custo_total' => $custo,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Valores da folha atualizados manualmente.');
    }

    public function destroy(FolhaPagamento $folhaPagamento)
    {
        $this->authorize('delete', $folhaPagamento);
        $folhaPagamento->delete();
        return redirect()->back()->with('success', 'Registro excluído.');
    }
}
