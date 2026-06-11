<?php

namespace App\Http\Controllers;

use App\Models\DocumentoRegistro;
use App\Models\DocumentoRevisao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreDocumentoRegistroRequest;
use App\Http\Requests\UpdateDocumentoRegistroRequest;
use App\Http\Requests\DestroyDocumentoRegistroRequest;
use App\Http\Requests\StoreRevisaoDocumentoRegistroRequest;
use App\Http\Requests\DestroyRevisaoDocumentoRegistroRequest;

class DocumentoRegistroController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-controle-documentos');

        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', \App\Models\Company::first()->id ?? null);
        }

        if (!$companyId) {
            abort(403, 'Nenhuma empresa associada ao usuário ou cadastrada no sistema.');
        }

        $documentos = DocumentoRegistro::with('revisoes.responsavel', 'revisoes.aprovador')
            ->where('company_id', $companyId)
            ->get();

        $companies = $user->is_master_admin ? \App\Models\Company::all(['id', 'nome_fantasia']) : [];
        
        $usersQuery = \App\Models\User::query();
        if (!$user->is_master_admin) {
            $usersQuery->where('company_id', $companyId);
        }
        $users = $usersQuery->get(['id', 'name']);

        return Inertia::render('ISO9001/DocumentosRegistros/Index', [
            'documentos' => $documentos,
            'companies' => $companies,
            'users' => $users,
            'currentCompanyId' => (int) $companyId
        ]);
    }

    public function store(StoreDocumentoRegistroRequest $request)
    {
        DB::beginTransaction();
        try {
            DocumentoRegistro::create($request->all());

            Log::info("Ação store realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Documento cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function update(UpdateDocumentoRegistroRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $documento = DocumentoRegistro::findOrFail($id);
            $documento->update($request->all());

            Log::info("Ação update realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Documento atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyDocumentoRegistroRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $documento = DocumentoRegistro::findOrFail($id);
            $documento->delete();

            Log::info("Ação destroy realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Documento excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function storeRevisao(StoreRevisaoDocumentoRegistroRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $documento = DocumentoRegistro::findOrFail($id);

            $revisao = new DocumentoRevisao($request->all());
            $revisao->documento_id = $documento->id;
            $revisao->save();

            // Optionally update the main document's current revision
            $documento->revisao_atual = $request->revisao;
            $documento->ano_ultima_revisao = date('Y', strtotime($request->data_revisao));
            $documento->save();

            Log::info("Ação storeRevisao realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Revisão registrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroyRevisao(DestroyRevisaoDocumentoRegistroRequest $request, $id, $revisaoId)
    {
        DB::beginTransaction();
        try {
            $revisao = DocumentoRevisao::where('documento_id', $id)->findOrFail($revisaoId);
            $revisao->delete();

            Log::info("Ação destroyRevisao realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Revisão excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }


}
