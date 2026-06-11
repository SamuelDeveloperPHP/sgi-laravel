<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoQualidade;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreObjetivoQualidadeRequest;
use App\Http\Requests\UpdateObjetivoQualidadeRequest;
use App\Http\Requests\DestroyObjetivoQualidadeRequest;
use App\Http\Requests\EnviarRevisaoObjetivoQualidadeRequest;
use App\Http\Requests\AprovarRevisaoObjetivoQualidadeRequest;
use App\Http\Requests\AprovarFinalObjetivoQualidadeRequest;
use App\Http\Requests\DevolverObjetivoQualidadeRequest;

class ObjetivoQualidadeController extends Controller
{


    public function index(Request $request)
    {
        $this->authorizePermission('view-objetivos-qualidade');

        $user = auth()->user();
        $query = ObjetivoQualidade::with(['elaborador', 'revisor', 'aprovador', 'responsaveis']);

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', Company::first()->id ?? null);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        }

        $objetivos = $query->orderBy('created_at', 'desc')->get();
        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/Objetivos/Index', [
            'objetivos' => $objetivos,
            'companies' => $companies,
            'currentCompanyId' => $user->is_master_admin ? (int) $request->input('company_id', Company::first()->id ?? null) : $user->company_id,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-objetivos-qualidade');

        $user = auth()->user();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        // Fetch users from the same company
        $usersQuery = User::query();
        if (!$user->is_master_admin) {
            $usersQuery->where('company_id', $companyId);
        }
        $users = $usersQuery->get(['id', 'name']);

        return Inertia::render('ISO9001/Objetivos/Form', [
            'objetivo' => new ObjetivoQualidade(),
            'users' => $users,
            'currentCompanyId' => $companyId
        ]);
    }

    public function store(StoreObjetivoQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;

            $objetivo = ObjetivoQualidade::create([
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'prazo' => $request->prazo,
                'status' => 'rascunho',
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
                'company_id' => $companyId
            ]);

            $objetivo->responsaveis()->sync($request->responsaveis);

            Log::info("Ação Store Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('objetivos-qualidade.show', $objetivo->id)
                ->with('success', 'Objetivo da Qualidade criado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show($id)
    {
        $this->authorizePermission('view-objetivos-qualidade');

        $objetivo = ObjetivoQualidade::with(['elaborador', 'revisor', 'aprovador', 'responsaveis', 'planosAcao'])
            ->findOrFail($id);

        return Inertia::render('ISO9001/Objetivos/Show', [
            'objetivo' => $objetivo
        ]);
    }

    public function edit(Request $request, $id)
    {
        $this->authorizePermission('manage-objetivos-qualidade');

        $objetivo = ObjetivoQualidade::with('responsaveis')->findOrFail($id);

        if (!in_array($objetivo->status, ['rascunho', 'devolvida'])) {
            return redirect()->route('objetivos-qualidade.show', $id)
                ->with('error', 'O objetivo não pode ser editado neste status.');
        }

        $user = auth()->user();
        $usersQuery = User::query();
        if (!$user->is_master_admin) {
            $usersQuery->where('company_id', $objetivo->company_id);
        }
        $users = $usersQuery->get(['id', 'name']);

        $objetivo->responsaveis_ids = $objetivo->responsaveis->pluck('id')->toArray();

        return Inertia::render('ISO9001/Objetivos/Form', [
            'objetivo' => $objetivo,
            'users' => $users,
            'currentCompanyId' => $objetivo->company_id
        ]);
    }

    public function update(UpdateObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);

            if (!in_array($objetivo->status, ['rascunho', 'devolvida'])) {
                return redirect()->route('objetivos-qualidade.show', $id)
                    ->with('error', 'O objetivo não pode ser editado neste status.');
            }

            $objetivo->update([
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'prazo' => $request->prazo,
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
                'status' => 'rascunho'
            ]);

            $objetivo->responsaveis()->sync($request->responsaveis);

            Log::info("Ação Update Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('objetivos-qualidade.show', $objetivo->id)
                ->with('success', 'Objetivo atualizado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);
            $objetivo->delete();

            Log::info("Ação Destroy Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->route('objetivos-qualidade.index')
                ->with('success', 'Objetivo excluído com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    // Fluxo de Aprovação

    public function enviarRevisao(EnviarRevisaoObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);

            if (!in_array($objetivo->status, ['rascunho', 'devolvida'])) {
                return back()->with('error', 'Status inválido.');
            }
            
            if (!$objetivo->revisor_id || !$objetivo->aprovador_id) {
                return back()->with('error', 'É necessário selecionar um Revisor e um Aprovador editando o objetivo antes de enviar para revisão.');
            }

            if ($objetivo->revisor_id == auth()->id()) {
                return back()->with('error', 'O elaborador não pode ser o revisor.');
            }

            $objetivo->update([
                'status' => 'aguardando_revisao',
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
            ]);

            Log::info("Ação Enviar Revisão Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('success', 'Enviado para revisão.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarRevisao(AprovarRevisaoObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);

            if ($objetivo->status !== 'aguardando_revisao') {
                return back()->with('error', 'Status inválido.');
            }

            if ($objetivo->revisor_id !== auth()->id()) {
                return back()->with('error', 'Apenas o revisor designado pode aprovar esta etapa.');
            }

            $objetivo->update([
                'status' => 'aguardando_aprovacao',
                'data_revisao' => now()
            ]);

            Log::info("Ação Aprovar Revisão Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('success', 'Revisão aprovada.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarFinal(AprovarFinalObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);

            if ($objetivo->status !== 'aguardando_aprovacao') {
                return back()->with('error', 'Status inválido.');
            }

            if ($objetivo->aprovador_id !== auth()->id()) {
                return back()->with('error', 'Apenas o aprovador designado pode realizar a aprovação final.');
            }

            $dataStr = $objetivo->id . now()->toIso8601String() . auth()->id() . substr($objetivo->titulo, 0, 100);
            $hash = hash('sha256', $dataStr);

            $objetivo->update([
                'status' => 'aprovada',
                'data_aprovacao' => now(),
                'hash_assinatura' => $hash
            ]);

            Log::info("Ação Aprovar Final Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('success', 'Aprovação final concluída.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function devolver(DevolverObjetivoQualidadeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $objetivo = ObjetivoQualidade::findOrFail($id);

            if (!in_array($objetivo->status, ['aguardando_revisao', 'aguardando_aprovacao'])) {
                return back()->with('error', 'Status inválido.');
            }

            $objetivo->update(['status' => 'devolvida']);
            
            Log::info("Ação Devolver Objetivo Qualidade realizada pelo usuário " . auth()->id());
            DB::commit();
            return back()->with('success', 'Devolvido com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function gerarPdf($id)
    {
        $this->authorizePermission('view-objetivos-qualidade');
        
        $objetivo = ObjetivoQualidade::with(['elaborador', 'revisor', 'aprovador', 'responsaveis'])->findOrFail($id);

        if ($objetivo->status !== 'aprovada') {
            abort(403, 'Apenas objetivos aprovados podem ser exportados.');
        }

        $company = Company::find($objetivo->company_id);

        $pdf = Pdf::loadView('pdf.objetivo_qualidade', [
            'objetivo' => $objetivo,
            'company' => $company
        ]);

        return $pdf->download('objetivo_qualidade_' . $objetivo->id . '.pdf');
    }
}
