<?php

namespace App\Http\Controllers;

use App\Models\MapaRisco;
use App\Models\Company;
use App\Models\User;
use App\Http\Requests\StoreMapaRiscoRequest;
use App\Http\Requests\UpdateMapaRiscoRequest;
use App\Http\Requests\DestroyMapaRiscoRequest;
use App\Notifications\MapaRiscoNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapaRiscoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-mapas-risco');

        $user = auth()->user();
        $query = MapaRisco::with(['company', 'aprovador', 'criador']);

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
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                  ->orWhere('setor', 'like', "%{$search}%");
            });
        }

        $mapas = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/MapasRisco/Index', [
            'mapas' => $mapas,
            'filters' => $request->only(['search', 'company_id']),
            'companies' => $companies,
            'currentCompanyId' => (int) ($companyId ?? $user->company_id)
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-mapas-risco');

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

        return Inertia::render('ISO9001/MapasRisco/Form', [
            'mapaRisco' => null,
            'users' => $users,
            'companies' => $companies,
            'companyId' => (int) $companyId
        ]);
    }

    public function store(StoreMapaRiscoRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['status'] = 'draft';

            $mapaRisco = MapaRisco::create($data);

            Log::info("Ação store realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('mapas-risco.index', ['company_id' => $mapaRisco->company_id])
                ->with('success', 'Mapa de Risco cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show(MapaRisco $mapasRisco)
    {
        $this->authorizePermission('view-mapas-risco');

        return Inertia::render('ISO9001/MapasRisco/Show', [
            'mapaRisco' => $mapasRisco->load(['company', 'aprovador', 'criador'])
        ]);
    }

    public function edit(MapaRisco $mapasRisco)
    {
        $this->authorizePermission('manage-mapas-risco');

        $user = auth()->user();
        
        $users = User::where('company_id', $mapasRisco->company_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/MapasRisco/Form', [
            'mapaRisco' => $mapasRisco,
            'users' => $users,
            'companies' => $companies,
            'companyId' => $mapasRisco->company_id
        ]);
    }

    public function update(UpdateMapaRiscoRequest $request, MapaRisco $mapasRisco)
    {
        DB::beginTransaction();
        try {
            $mapasRisco->update($request->validated());

            Log::info("Ação update realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->route('mapas-risco.index', ['company_id' => $mapasRisco->company_id])
                ->with('success', 'Mapa de Risco atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyMapaRiscoRequest $request, MapaRisco $mapasRisco)
    {
        DB::beginTransaction();
        try {
            $mapasRisco->delete();

            Log::info("Ação destroy realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Mapa de Risco excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    /**
     * Envia o Mapa de Risco para a fila de aprovação do usuário selecionado.
     */
    public function enviarAprovacao(MapaRisco $mapasRisco)
    {
        $this->authorizePermission('manage-mapas-risco');

        DB::beginTransaction();
        try {
            if ($mapasRisco->status !== 'draft' && $mapasRisco->status !== 'rejected') {
                abort(400, 'Apenas rascunhos ou mapas rejeitados podem ser enviados para aprovação.');
            }

            $mapasRisco->update(['status' => 'pending_approval']);

            $aprovador = $mapasRisco->aprovador;
            if ($aprovador) {
                $aprovador->notify(new MapaRiscoNotification(
                    $mapasRisco,
                    'Mapa de Risco Pendente',
                    "O mapa de risco '{$mapasRisco->titulo}' do setor {$mapasRisco->setor} aguarda sua aprovação."
                ));
            }

            Log::info("Ação enviarAprovacao realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Mapa de Risco enviado para aprovação!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    /**
     * Aprova o Mapa de Risco.
     */
    public function aprovar(MapaRisco $mapasRisco)
    {
        $user = auth()->user();
        if ($user->id !== $mapasRisco->aprovador_id && !$user->is_master_admin) {
            abort(403, 'Apenas o aprovador selecionado pode realizar esta ação.');
        }

        DB::beginTransaction();
        try {
            if ($mapasRisco->status !== 'pending_approval') {
                abort(400, 'Apenas mapas pendentes de aprovação podem ser aprovados.');
            }

            $mapasRisco->update(['status' => 'approved']);

            $criador = $mapasRisco->criador;
            if ($criador) {
                $criador->notify(new MapaRiscoNotification(
                    $mapasRisco,
                    'Mapa de Risco Aprovado',
                    "O mapa de risco '{$mapasRisco->titulo}' do setor {$mapasRisco->setor} foi aprovado com sucesso."
                ));
            }

            Log::info("Ação aprovar realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Mapa de Risco aprovado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    /**
     * Rejeita o Mapa de Risco com uma justificativa.
     */
    public function rejeitar(Request $request, MapaRisco $mapasRisco)
    {
        $user = auth()->user();
        if ($user->id !== $mapasRisco->aprovador_id && !$user->is_master_admin) {
            abort(403, 'Apenas o aprovador selecionado pode realizar esta ação.');
        }

        $request->validate([
            'motivo_rejeicao' => 'required|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            if ($mapasRisco->status !== 'pending_approval') {
                abort(400, 'Apenas mapas pendentes de aprovação podem ser rejeitados.');
            }

            $mapasRisco->update([
                'status' => 'rejected',
                'motivo_rejeicao' => $request->input('motivo_rejeicao')
            ]);

            $criador = $mapasRisco->criador;
            if ($criador) {
                $criador->notify(new MapaRiscoNotification(
                    $mapasRisco,
                    'Mapa de Risco Rejeitado',
                    "O mapa de risco '{$mapasRisco->titulo}' do setor {$mapasRisco->setor} foi rejeitado. Motivo: " . substr($request->input('motivo_rejeicao'), 0, 50) . "..."
                ));
            }

            Log::info("Ação rejeitar realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return redirect()->back()->with('success', 'Mapa de Risco rejeitado!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }
}
