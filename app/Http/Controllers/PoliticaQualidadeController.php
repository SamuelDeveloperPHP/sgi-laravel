<?php

namespace App\Http\Controllers;

use App\Models\PoliticaQualidade;
use Illuminate\Http\Request;
use App\Http\Requests\SalvarRascunhoPoliticaQualidadeRequest;
use App\Http\Requests\EnviarRevisaoPoliticaQualidadeRequest;
use App\Http\Requests\AprovarRevisaoPoliticaQualidadeRequest;
use App\Http\Requests\AprovarFinalPoliticaQualidadeRequest;
use App\Http\Requests\DevolverPoliticaQualidadeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PoliticaQualidadeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-politica-qualidade');

        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', \App\Models\Company::first()->id ?? null);
        }

        if (!$companyId) {
            abort(403, 'Nenhuma empresa associada ao usuário ou cadastrada no sistema.');
        }

        $politica = PoliticaQualidade::with(['elaborador', 'revisor', 'aprovador'])
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

        return Inertia::render('ISO9001/PoliticaQualidade/Index', [
            'politica' => $politica,
            'companies' => $companies,
            'users' => $users,
            'currentCompanyId' => (int) $companyId
        ]);
    }

    private function getPolitica(Request $request)
    {
        $user = auth()->user();
        $query = PoliticaQualidade::query();

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id');
            if (!$companyId) {
                abort(400, 'ID da empresa é obrigatório para administradores master.');
            }
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    public function salvarRascunho(SalvarRascunhoPoliticaQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $politica = $this->getPolitica($request);

            if (!in_array($politica->status, ['rascunho', 'devolvida'])) {
                return back()->with('error', 'A política não pode ser editada neste status.');
            }

            $politica->update([
                'conteudo' => $request->conteudo,
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
                'status' => 'rascunho'
            ]);

            Log::info("Ação salvar rascunho de política realizada pelo usuário " . auth()->user()->id);
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

    public function enviarRevisao(EnviarRevisaoPoliticaQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $politica = $this->getPolitica($request);

            if (!in_array($politica->status, ['rascunho', 'devolvida'])) {
                return back()->with('error', 'A política não pode ser enviada para revisão neste status.');
            }

            if ($request->revisor_id == auth()->id()) {
                return back()->with('error', 'O elaborador não pode ser o revisor.');
            }

            $politica->update([
                'conteudo' => $request->conteudo,
                'status' => 'aguardando_revisao',
                'revisor_id' => $request->revisor_id,
                'aprovador_id' => $request->aprovador_id,
                'elaborador_id' => auth()->id(),
                'data_elaboracao' => now(),
            ]);

            Log::info("Ação enviar política para revisão realizada pelo usuário " . auth()->user()->id);
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

    public function aprovarRevisao(AprovarRevisaoPoliticaQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $politica = $this->getPolitica($request);

            if ($politica->status !== 'aguardando_revisao') {
                return back()->with('error', 'A política não está aguardando revisão.');
            }

            if ($politica->revisor_id !== auth()->id()) {
                return back()->with('error', 'Apenas o revisor designado pode aprovar esta etapa.');
            }

            $politica->update([
                'status' => 'aguardando_aprovacao',
                'data_revisao' => now()
            ]);

            Log::info("Ação aprovar revisão de política realizada pelo usuário " . auth()->user()->id);
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

    public function aprovarFinal(AprovarFinalPoliticaQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $politica = $this->getPolitica($request);

            if ($politica->status !== 'aguardando_aprovacao') {
                return back()->with('error', 'A política não está aguardando aprovação final.');
            }

            if ($politica->aprovador_id !== auth()->id()) {
                return back()->with('error', 'Apenas o aprovador designado pode realizar a aprovação final.');
            }

            $dataStr = $politica->company_id . now()->toIso8601String() . auth()->id() . substr($politica->conteudo, 0, 100);
            $hash = hash('sha256', $dataStr);

            $politica->update([
                'status' => 'aprovada',
                'data_aprovacao' => now(),
                'hash_assinatura' => $hash
            ]);

            Log::info("Ação aprovar final de política realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return back()->with('success', 'Política aprovada com sucesso! Assinatura Hash gerada.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function devolver(DevolverPoliticaQualidadeRequest $request)
    {
        DB::beginTransaction();
        try {
            $politica = $this->getPolitica($request);

            if (!in_array($politica->status, ['aguardando_revisao', 'aguardando_aprovacao'])) {
                return back()->with('error', 'Não é possível devolver neste status.');
            }

            $politica->update([
                'status' => 'devolvida'
            ]);

            Log::info("Ação devolver política realizada pelo usuário " . auth()->user()->id);
            DB::commit();

            return back()->with('success', 'Política devolvida para o elaborador.');
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
        $this->authorizePermission('view-politica-qualidade');

        $politica = PoliticaQualidade::with(['elaborador', 'revisor', 'aprovador']);
        
        $user = auth()->user();
        if ($user->is_master_admin) {
            $companyId = $request->input('company_id');
            if ($companyId) {
                $politica->where('company_id', $companyId);
            }
        }
        $politica = $politica->firstOrFail();

        if ($politica->status !== 'aprovada') {
            abort(403, 'Apenas políticas aprovadas podem ser exportadas em PDF.');
        }

        // Obter a logo da empresa (simulando, já que precisamos adicionar lógica da company futuramente)
        $company = \App\Models\Company::find($politica->company_id);

        $data = [
            'politica' => $politica,
            'company' => $company
        ];

        // Você pode criar uma view 'pdf.politica_qualidade'
        $pdf = Pdf::loadView('pdf.politica_qualidade', $data);

        return $pdf->download('politica_da_qualidade.pdf');
    }

}
