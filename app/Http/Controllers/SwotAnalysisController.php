<?php

namespace App\Http\Controllers;

use App\Models\SwotAnalysis;
use App\Models\Company;
use App\Models\User;
use App\Notifications\MapaRiscoNotification; // Reuse or create SwotNotification
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SwotAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-analise-swot');

        $user = auth()->user();
        $query = SwotAnalysis::with(['company', 'aprovador', 'criador']);

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
            $search = $request->input('search');
            $query->where('titulo', 'like', "%{$search}%");
        }

        $analises = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/AnaliseSwot/Index', [
            'analises' => $analises,
            'filters' => $request->only(['search', 'company_id']),
            'companies' => $companies,
            'currentCompanyId' => (int) ($companyId ?? $user->company_id)
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-analise-swot');

        $user = auth()->user();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        if (!$companyId && $user->is_master_admin) {
            $companyId = Company::first()->id ?? null;
        }

        if (!$companyId) {
            abort(400, 'Empresa não identificada.');
        }

        $users = User::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/AnaliseSwot/Form', [
            'analise' => null,
            'users' => $users,
            'companies' => $companies,
            'companyId' => (int) $companyId
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage-analise-swot');

        $user = $request->user();
        if (!$user->is_master_admin) {
            $request->merge(['company_id' => $user->company_id]);
        }
        
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'titulo' => 'required|string|max:255',
            'data_analise' => 'required|date',
            'aprovador_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $request->input('company_id')),
            ],
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'opportunities' => 'nullable|array',
            'threats' => 'nullable|array',
            'cruzamentos' => 'nullable|array',
            'planos_acao' => 'nullable|array',
            'objetivo_estrategico' => 'nullable|string|max:1000',
            'conclusao' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $data['status'] = 'draft';

            $analise = SwotAnalysis::create($data);

            Log::info("Ação store em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('analise-swot.index', ['company_id' => $analise->company_id])
                ->with('success', 'Análise SWOT cadastrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show($id)
    {
        $this->authorizePermission('view-analise-swot');
        $analise = SwotAnalysis::with(['company', 'aprovador', 'criador'])->findOrFail($id);

        return Inertia::render('ISO9001/AnaliseSwot/Show', [
            'analise' => $analise
        ]);
    }

    public function edit($id)
    {
        $this->authorizePermission('manage-analise-swot');
        $analise = SwotAnalysis::findOrFail($id);

        $user = auth()->user();
        
        $users = User::where('company_id', $analise->company_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/AnaliseSwot/Form', [
            'analise' => $analise,
            'users' => $users,
            'companies' => $companies,
            'companyId' => $analise->company_id
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('manage-analise-swot');
        $analise = SwotAnalysis::findOrFail($id);

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'data_analise' => 'required|date',
            'aprovador_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $analise->company_id),
            ],
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'opportunities' => 'nullable|array',
            'threats' => 'nullable|array',
            'cruzamentos' => 'nullable|array',
            'planos_acao' => 'nullable|array',
            'objetivo_estrategico' => 'nullable|string|max:1000',
            'conclusao' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $analise->update($data);

            Log::info("Ação update em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('analise-swot.index', ['company_id' => $analise->company_id])
                ->with('success', 'Análise SWOT atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy($id)
    {
        $this->authorizePermission('manage-analise-swot');
        $analise = SwotAnalysis::findOrFail($id);

        DB::beginTransaction();
        try {
            $analise->delete();

            Log::info("Ação destroy em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Análise SWOT excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function enviarAprovacao($id)
    {
        $this->authorizePermission('manage-analise-swot');
        $analise = SwotAnalysis::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($analise->status !== 'draft' && $analise->status !== 'rejected') {
                abort(400, 'Apenas rascunhos ou análises rejeitadas podem ser enviadas para aprovação.');
            }

            $analise->update(['status' => 'pending_approval']);

            // Reuse MapaRiscoNotification temporarily or create a generic one later
            $aprovador = $analise->aprovador;
            if ($aprovador) {
                $aprovador->notify(new MapaRiscoNotification(
                    $analise, // Hack: using the same notification class that accepts model
                    'Análise SWOT Pendente',
                    "A análise SWOT '{$analise->titulo}' aguarda sua aprovação."
                ));
            }

            Log::info("Ação enviarAprovacao em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Análise SWOT enviada para aprovação!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovar($id)
    {
        $analise = SwotAnalysis::findOrFail($id);
        $user = auth()->user();
        
        if ($user->id !== $analise->aprovador_id && !$user->is_master_admin) {
            abort(403, 'Apenas o aprovador selecionado pode realizar esta ação.');
        }

        DB::beginTransaction();
        try {
            if ($analise->status !== 'pending_approval') {
                abort(400, 'Apenas análises pendentes de aprovação podem ser aprovadas.');
            }

            $analise->update(['status' => 'approved']);

            $criador = $analise->criador;
            if ($criador) {
                $criador->notify(new MapaRiscoNotification(
                    $analise,
                    'Análise SWOT Aprovada',
                    "A análise SWOT '{$analise->titulo}' foi aprovada com sucesso."
                ));
            }

            Log::info("Ação aprovar em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Análise SWOT aprovada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function rejeitar(Request $request, $id)
    {
        $analise = SwotAnalysis::findOrFail($id);
        $user = auth()->user();
        
        if ($user->id !== $analise->aprovador_id && !$user->is_master_admin) {
            abort(403, 'Apenas o aprovador selecionado pode realizar esta ação.');
        }

        $request->validate([
            'motivo_rejeicao' => 'required|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            if ($analise->status !== 'pending_approval') {
                abort(400, 'Apenas análises pendentes de aprovação podem ser rejeitadas.');
            }

            $analise->update([
                'status' => 'rejected',
                'motivo_rejeicao' => $request->input('motivo_rejeicao')
            ]);

            $criador = $analise->criador;
            if ($criador) {
                $criador->notify(new MapaRiscoNotification(
                    $analise,
                    'Análise SWOT Rejeitada',
                    "A análise SWOT '{$analise->titulo}' foi rejeitada. Motivo: " . substr($request->input('motivo_rejeicao'), 0, 50) . "..."
                ));
            }

            Log::info("Ação rejeitar em SwotAnalysis realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Análise SWOT rejeitada!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }
}
