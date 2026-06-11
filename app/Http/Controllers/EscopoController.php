<?php

namespace App\Http\Controllers;

use App\Models\Escopo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SalvarRascunhoEscopoRequest;
use App\Http\Requests\EnviarRevisaoEscopoRequest;
use App\Http\Requests\AprovarRevisaoEscopoRequest;
use App\Http\Requests\AprovarFinalEscopoRequest;
use App\Http\Requests\DevolverEscopoRequest;

class EscopoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-escopo');

        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', \App\Models\Company::first()->id ?? null);
        }

        if (!$companyId) {
            abort(403, 'Nenhuma empresa associada ao usuário ou cadastrada no sistema.');
        }

        $escopo = Escopo::with(['elaborador', 'revisor', 'aprovador'])
            ->firstOrCreate(
                ['company_id' => $companyId],
                [
                    'status' => 'rascunho',
                    'conteudo' => ''
                ]
            );

        $companies = $user->is_master_admin ? \App\Models\Company::all(['id', 'nome_fantasia']) : [];
        
        $usersQuery = \App\Models\User::query();
        if (!$user->is_master_admin) {
            $usersQuery->where('company_id', $companyId);
        }
        $users = $usersQuery->get(['id', 'name']);

        return Inertia::render('ISO9001/Escopo/Index', [
            'escopo' => $escopo,
            'companies' => $companies,
            'users' => $users,
            'currentCompanyId' => (int) $companyId
        ]);
    }

    private function getEscopo(Request $request)
    {
        $user = auth()->user();
        $query = Escopo::query();

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id');
            if (!$companyId) {
                abort(400, 'ID da empresa é obrigatório para administradores master.');
            }
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    public function salvarRascunho(SalvarRascunhoEscopoRequest $request)
    {
        DB::beginTransaction();
        try {
            $escopo = $this->getEscopo($request);

            if (!in_array($escopo->status, ['rascunho', 'devolvida'])) {
                return back()->with('error', 'O escopo não pode ser editado neste status.');
            }

            $escopo->update([
                'conteudo' => $request->conteudo,
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
                'status' => 'rascunho'
            ]);

            Log::info("Ação salvarRascunho realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('success', 'Rascunho salvo com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function enviarRevisao(EnviarRevisaoEscopoRequest $request)
    {
        DB::beginTransaction();
        try {
            $escopo = $this->getEscopo($request);

            if (!in_array($escopo->status, ['rascunho', 'devolvida'])) {
                return back()->with('error', 'O escopo não pode ser enviado para revisão neste status.');
            }

            if ($request->revisor_id == auth()->id()) {
                return back()->with('error', 'O elaborador não pode ser o revisor.');
            }

            $escopo->update([
                'conteudo' => $request->conteudo,
                'status' => 'aguardando_revisao',
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
            ]);

            Log::info("Ação enviarRevisao realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('success', 'Enviado para revisão com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarRevisao(AprovarRevisaoEscopoRequest $request)
    {
        DB::beginTransaction();
        try {
            $escopo = $this->getEscopo($request);

            if ($escopo->status !== 'aguardando_revisao') {
                return back()->with('error', 'O escopo não está aguardando revisão.');
            }

            if ($escopo->revisor_id !== auth()->id()) {
                return back()->with('error', 'Apenas o revisor designado pode aprovar esta etapa.');
            }

            $escopo->update([
                'status' => 'aguardando_aprovacao',
                'data_revisao' => now()
            ]);

            Log::info("Ação aprovarRevisao realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('success', 'Revisão aprovada. Enviado para aprovação final.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarFinal(AprovarFinalEscopoRequest $request)
    {
        DB::beginTransaction();
        try {
            $escopo = $this->getEscopo($request);

            if ($escopo->status !== 'aguardando_aprovacao') {
                return back()->with('error', 'O escopo não está aguardando aprovação final.');
            }

            if ($escopo->aprovador_id !== auth()->id()) {
                return back()->with('error', 'Apenas o aprovador designado pode realizar a aprovação final.');
            }

            $dataStr = $escopo->company_id . now()->toIso8601String() . auth()->id() . substr($escopo->conteudo, 0, 100);
            $hash = hash('sha256', $dataStr);

            $escopo->update([
                'status' => 'aprovada',
                'data_aprovacao' => now(),
                'hash_assinatura' => $hash
            ]);

            Log::info("Ação aprovarFinal realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('success', 'Escopo aprovado com sucesso! Assinatura Hash gerada.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function devolver(DevolverEscopoRequest $request)
    {
        DB::beginTransaction();
        try {
            $escopo = $this->getEscopo($request);

            if (!in_array($escopo->status, ['aguardando_revisao', 'aguardando_aprovacao'])) {
                return back()->with('error', 'Não é possível devolver neste status.');
            }

            $escopo->update([
                'status' => 'devolvida'
            ]);

            Log::info("Ação devolver realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return back()->with('success', 'Escopo devolvido para o elaborador.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function gerarPdf(Request $request)
    {
        $this->authorizePermission('view-escopo');

        $escopo = Escopo::with(['elaborador', 'revisor', 'aprovador']);
        
        $user = auth()->user();
        if ($user->is_master_admin) {
            $companyId = $request->input('company_id');
            if ($companyId) {
                $escopo->where('company_id', $companyId);
            }
        }
        $escopo = $escopo->firstOrFail();

        if ($escopo->status !== 'aprovada') {
            abort(403, 'Apenas escopos aprovados podem ser exportados em PDF.');
        }

        $company = \App\Models\Company::find($escopo->company_id);

        $data = [
            'escopo' => $escopo,
            'company' => $company
        ];

        $pdf = Pdf::loadView('pdf.escopo', $data);

        return $pdf->download('escopo_do_sgi.pdf');
    }

}
