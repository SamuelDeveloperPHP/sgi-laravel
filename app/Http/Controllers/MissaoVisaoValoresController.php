<?php

namespace App\Http\Controllers;

use App\Models\MissaoVisaoValores;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SalvarRascunhoMissaoVisaoValoresRequest;
use App\Http\Requests\EnviarRevisaoMissaoVisaoValoresRequest;
use App\Http\Requests\AprovarRevisaoMissaoVisaoValoresRequest;
use App\Http\Requests\AprovarFinalMissaoVisaoValoresRequest;
use App\Http\Requests\DevolverMissaoVisaoValoresRequest;

class MissaoVisaoValoresController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-missao-visao-valores');

        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', \App\Models\Company::first()->id ?? null);
        }

        if (!$companyId) {
            abort(403, 'Nenhuma empresa associada ao usuário ou cadastrada no sistema.');
        }

        $mvv = MissaoVisaoValores::with(['elaborador', 'revisor', 'aprovador'])
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

        return Inertia::render('ISO9001/MissaoVisaoValores/Index', [
            'mvv' => $mvv,
            'companies' => $companies,
            'users' => $users,
            'currentCompanyId' => (int) $companyId
        ]);
    }

    private function getMvv(Request $request)
    {
        $user = auth()->user();
        $query = MissaoVisaoValores::query();

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id');
            if (!$companyId) {
                abort(400, 'ID da empresa é obrigatório para administradores master.');
            }
            $query->where('company_id', $companyId);
        } else {
            $query->where('company_id', $user->company_id);
        }

        return $query->firstOrFail();
    }

    public function salvarRascunho(SalvarRascunhoMissaoVisaoValoresRequest $request)
    {
        DB::beginTransaction();
        try {
            $mvv = $this->getMvv($request);

            if (!in_array($mvv->status, ['rascunho', 'devolvida'])) {
                abort(403, 'Não é possível editar este documento no status atual.');
            }

            $mvv->conteudo = $request->conteudo;
            $mvv->revisor_id = $request->revisor_id;
            $mvv->aprovador_id = $request->aprovador_id;
            $mvv->user_edit = auth()->id();
            $mvv->save();

            Log::info("Ação Salvar Rascunho Missao Visao Valores realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Rascunho salvo com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function enviarRevisao(EnviarRevisaoMissaoVisaoValoresRequest $request)
    {
        DB::beginTransaction();
        try {
            $mvv = $this->getMvv($request);

            if (!in_array($mvv->status, ['rascunho', 'devolvida'])) {
                abort(403, 'Ação não permitida neste status.');
            }

            $mvv->conteudo = $request->conteudo;
            $mvv->status = 'aguardando_revisao';
            $mvv->elaborador_id = auth()->id();
            $mvv->data_elaboracao = now();
            $mvv->revisor_id = $request->revisor_id;
            $mvv->aprovador_id = $request->aprovador_id;
            $mvv->user_edit = auth()->id();
            $mvv->save();

            Log::info("Ação Enviar Revisão Missao Visao Valores realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Enviado para revisão!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarRevisao(AprovarRevisaoMissaoVisaoValoresRequest $request)
    {
        DB::beginTransaction();
        try {
            $mvv = $this->getMvv($request);

            if ($mvv->status !== 'aguardando_revisao') {
                abort(403, 'Documento não está aguardando revisão.');
            }

            if ($mvv->revisor_id !== auth()->id() && !auth()->user()->is_master_admin) {
                abort(403, 'Apenas o Revisor designado pode aprovar esta etapa.');
            }

            $mvv->status = 'aguardando_aprovacao';
            $mvv->data_revisao = now();
            $mvv->user_edit = auth()->id();
            $mvv->save();

            Log::info("Ação Aprovar Revisão Missao Visao Valores realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Revisão concluída. Enviado para aprovação final.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function aprovarFinal(AprovarFinalMissaoVisaoValoresRequest $request)
    {
        DB::beginTransaction();
        try {
            $mvv = $this->getMvv($request);

            if ($mvv->status !== 'aguardando_aprovacao') {
                abort(403, 'Documento não está aguardando aprovação final.');
            }

            if ($mvv->aprovador_id !== auth()->id() && !auth()->user()->is_master_admin) {
                abort(403, 'Apenas o Aprovador designado pode aprovar esta etapa.');
            }

            $mvv->status = 'aprovada';
            $mvv->data_aprovacao = now();
            $mvv->hash_assinatura = hash('sha256', $mvv->id . $mvv->conteudo . now() . Str::random(10));
            $mvv->user_edit = auth()->id();
            $mvv->save();

            Log::info("Ação Aprovar Final Missao Visao Valores realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Documento APROVADO com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function devolver(DevolverMissaoVisaoValoresRequest $request)
    {
        DB::beginTransaction();
        try {
            $mvv = $this->getMvv($request);

            if (!in_array($mvv->status, ['aguardando_revisao', 'aguardando_aprovacao'])) {
                abort(403, 'Ação não permitida neste status.');
            }

            if ($mvv->status === 'aguardando_revisao' && $mvv->revisor_id !== auth()->id() && !auth()->user()->is_master_admin) {
                abort(403, 'Apenas o Revisor designado pode devolver nesta etapa.');
            }

            if ($mvv->status === 'aguardando_aprovacao' && $mvv->aprovador_id !== auth()->id() && !auth()->user()->is_master_admin) {
                abort(403, 'Apenas o Aprovador designado pode devolver nesta etapa.');
            }

            $mvv->status = 'devolvida';
            $mvv->user_edit = auth()->id();
            
            $mvv->elaborador_id = null;
            $mvv->data_elaboracao = null;
            $mvv->data_revisao = null;
            $mvv->data_aprovacao = null;
            $mvv->hash_assinatura = null;
            
            $mvv->save();

            Log::info("Ação Devolver Missao Visao Valores realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Documento devolvido para correção.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function exportarPdf(Request $request)
    {
        $this->authorizePermission('view-missao-visao-valores');
        
        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->query('company_id');
            if (!$companyId) {
                abort(400, 'ID da empresa não informado.');
            }
        }

        $mvv = MissaoVisaoValores::with(['elaborador', 'revisor', 'aprovador'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        $company = \App\Models\Company::findOrFail($companyId);

        $pdf = Pdf::loadView('pdf.missao_visao_valores', [
            'mvv' => $mvv,
            'company' => $company
        ]);

        return $pdf->download("Missao_Visao_Valores_{$company->nome_fantasia}.pdf");
    }


}
