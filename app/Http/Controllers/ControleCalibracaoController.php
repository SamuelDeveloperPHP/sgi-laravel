<?php

namespace App\Http\Controllers;

use App\Models\ControleCalibracao;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreControleCalibracaoRequest;
use App\Http\Requests\UpdateControleCalibracaoRequest;
use App\Http\Requests\DestroyControleCalibracaoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ControleCalibracaoController extends Controller
{


    public function index(Request $request)
    {
        $this->authorizePermission('view-controle-calibracoes');

        $user = auth()->user();
        
        $query = ControleCalibracao::query();

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', Company::first()->id ?? null);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
            $companyId = $user->company_id;
        }

        if ($request->filled('search')) {
            $query->where('equipamento', 'like', '%' . $request->search . '%');
        }

        $calibracoes = $query->orderBy('data_proxima_calibracao', 'asc')->paginate(10)->withQueryString();

        $calibracoes->getCollection()->transform(function ($item) {
            $hoje = Carbon::now();
            $dias = $hoje->diffInDays($item->data_proxima_calibracao, false);
            
            if (!$item->data_proxima_calibracao) {
                $item->status_prazo = 'Sem Data';
                $item->status_cor = 'gray';
            } elseif ($dias < 0) {
                $item->status_prazo = 'Vencido';
                $item->status_cor = 'red';
            } elseif ($dias <= 30) {
                $item->status_prazo = 'Vence em ' . $dias . ' dias';
                $item->status_cor = 'yellow';
            } else {
                $item->status_prazo = 'No Prazo';
                $item->status_cor = 'green';
            }
            return $item;
        });

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/Calibracoes/Index', [
            'calibracoes' => $calibracoes,
            'filters' => $request->only(['search', 'company_id']),
            'companies' => $companies,
            'currentCompanyId' => (int) ($companyId ?? $user->company_id)
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-controle-calibracoes');
        
        $user = auth()->user();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        if (!$companyId) {
            abort(400, 'Empresa não identificada.');
        }

        return Inertia::render('ISO9001/Calibracoes/Form', [
            'calibracao' => null,
            'companyId' => (int) $companyId
        ]);
    }

    public function store(StoreControleCalibracaoRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->except(['arquivo']);
            $data['created_by'] = auth()->id();

            if ($request->hasFile('arquivo')) {
                $data['arquivo_certificado'] = $request->file('arquivo')->store(
                    'companies/' . $data['company_id'] . '/certificados-calibracao',
                    'local'
                );
            }

            ControleCalibracao::create($data);

            Log::info("Ação store realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('controle-calibracoes.index', ['company_id' => $request->company_id])
                ->with('success', 'Equipamento cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function edit(ControleCalibracao $controleCalibraco)
    {
        $this->authorizePermission('manage-controle-calibracoes');
        return Inertia::render('ISO9001/Calibracoes/Form', [
            'calibracao' => $controleCalibraco,
            'companyId' => $controleCalibraco->company_id
        ]);
    }

    public function update(UpdateControleCalibracaoRequest $request, ControleCalibracao $controleCalibraco)
    {
        DB::beginTransaction();
        try {
            $data = $request->except(['arquivo']);
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('arquivo')) {
                if ($controleCalibraco->arquivo_certificado) {
                    Storage::disk('local')->delete($controleCalibraco->arquivo_certificado);
                }
                $data['arquivo_certificado'] = $request->file('arquivo')->store(
                    'companies/' . $controleCalibraco->company_id . '/certificados-calibracao',
                    'local'
                );
            }

            $controleCalibraco->update($data);

            Log::info("Ação update realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('controle-calibracoes.index', ['company_id' => $controleCalibraco->company_id])
                ->with('success', 'Equipamento atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyControleCalibracaoRequest $request, ControleCalibracao $controleCalibraco)
    {
        DB::beginTransaction();
        try {
            if ($controleCalibraco->arquivo_certificado) {
                Storage::disk('local')->delete($controleCalibraco->arquivo_certificado);
            }
            
            $controleCalibraco->delete();
            
            Log::info("Ação destroy realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Equipamento excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function downloadArquivo(ControleCalibracao $controleCalibraco)
    {
        $this->authorizePermission('view-controle-calibracoes');

        if (!$controleCalibraco->arquivo_certificado || !Storage::disk('local')->exists($controleCalibraco->arquivo_certificado)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return Storage::disk('local')->download($controleCalibraco->arquivo_certificado);
    }

    public function exportarPdf(Request $request)
    {
        $this->authorizePermission('view-controle-calibracoes');

        $user = auth()->user();
        
        $query = ControleCalibracao::query();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        if ($companyId) {
            $query->where('company_id', $companyId);
            $empresa = Company::find($companyId);
        } else {
            abort(400, 'Selecione uma empresa.');
        }

        $calibracoes = $query->orderBy('data_proxima_calibracao', 'asc')->get();

        $pdf = Pdf::loadView('pdf.planilha_calibracoes', [
            'calibracoes' => $calibracoes,
            'empresa' => $empresa
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download("Controle_Calibracoes_{$empresa->nome_fantasia}.pdf");
    }
}
