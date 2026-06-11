<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\Company;
use App\Http\Requests\StoreFornecedorRequest;
use App\Http\Requests\UpdateFornecedorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FornecedorController extends Controller
{


    public function index(Request $request)
    {
        $this->authorizePermission('view-fornecedores');

        $user = auth()->user();
        $query = Fornecedor::query();

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
            $query->where('razao_social', 'like', '%' . $request->search . '%')
                  ->orWhere('cnpj_cpf', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status_homologacao', $request->status);
        }

        $fornecedores = $query->orderBy('razao_social', 'asc')->paginate(10)->withQueryString();

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];
        $currentCompanyObj = Company::find($companyId ?? $user->company_id);

        return Inertia::render('ISO9001/Fornecedores/Index', [
            'fornecedores' => $fornecedores,
            'filters' => $request->only(['search', 'company_id', 'status']),
            'companies' => $companies,
            'currentCompanyId' => (int) ($companyId ?? $user->company_id),
            'criteriosPadraoDB' => $currentCompanyObj ? $currentCompanyObj->criterios_avaliacao_fornecedor : null,
        ]);
    }

    public function saveCriteriosPadrao(Request $request, $companyId)
    {
        $this->authorizePermission('manage-fornecedores');
        
        $request->validate([
            'criterios' => 'nullable|array'
        ]);

        $company = Company::findOrFail($companyId);
        $company->criterios_avaliacao_fornecedor = $request->criterios;
        $company->save();

        return redirect()->back()->with('success', 'Critérios padrão atualizados com sucesso.');
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-fornecedores');
        
        $user = auth()->user();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        return Inertia::render('ISO9001/Fornecedores/Form', [
            'fornecedor' => null,
            'companyId' => (int) $companyId
        ]);
    }

    public function store(StoreFornecedorRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $fornecedor = Fornecedor::create($data);

            Log::info("Usuário " . auth()->id() . " criou o fornecedor ID: {$fornecedor->id}");
            DB::commit();

            return redirect()->route('fornecedores.index', ['company_id' => $request->company_id])
                ->with('success', 'Fornecedor cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar fornecedor: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao cadastrar fornecedor. Tente novamente.');
        }
    }

    public function show($id)
    {
        $this->authorizePermission('view-fornecedores');

        $fornecedor = Fornecedor::with([
            'documentos' => function($q) { $q->orderBy('created_at', 'desc'); },
            'avaliacoes.avaliador',
            'documentos.avaliador',
            'empresa'
        ])->findOrFail($id);

        return Inertia::render('ISO9001/Fornecedores/Show', [
            'fornecedor' => $fornecedor,
            'criteriosPadrao' => $fornecedor->empresa->criterios_avaliacao_fornecedor
        ]);
    }

    public function edit(Fornecedor $fornecedore)
    {
        $this->authorizePermission('manage-fornecedores');
        return Inertia::render('ISO9001/Fornecedores/Form', [
            'fornecedor' => $fornecedore,
            'companyId' => $fornecedore->company_id
        ]);
    }

    public function update(UpdateFornecedorRequest $request, Fornecedor $fornecedore)
    {
        DB::beginTransaction();

        
        try {
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            $fornecedore->update($data);

            Log::info("Usuário " . auth()->id() . " atualizou o fornecedor ID: {$fornecedore->id}");
            DB::commit();

            return redirect()->route('fornecedores.index', ['company_id' => $fornecedore->company_id])
                ->with('success', 'Fornecedor atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao atualizar fornecedor ID {$fornecedore->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao atualizar fornecedor. Tente novamente.');
        }
    }

    public function destroy(Fornecedor $fornecedore)
    {
        $this->authorizePermission('manage-fornecedores');

        DB::beginTransaction();
        try {
            $companyId = $fornecedore->company_id;
            $id = $fornecedore->id;
            $fornecedore->delete();

            Log::info("Usuário " . auth()->id() . " excluiu o fornecedor ID: {$id}");
            DB::commit();

            return redirect()->route('fornecedores.index', ['company_id' => $companyId])
                ->with('success', 'Fornecedor removido!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir fornecedor ID {$fornecedore->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao excluir fornecedor. Tente novamente.');
        }
    }
}
