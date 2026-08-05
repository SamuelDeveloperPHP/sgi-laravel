<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\NossaHistoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SalvarNossaHistoriaRequest;

class NossaHistoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-nossa-historia');

        $user = auth()->user();
        $companyId = $user->company_id;

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', \App\Models\Company::first()->id ?? null);
        }

        if (!$companyId) {
            abort(403, 'Nenhuma empresa associada ao usuário ou cadastrada no sistema.');
        }

        $company = Company::findOrFail($companyId);

        $historia = NossaHistoria::firstOrCreate(
            ['company_id' => $companyId],
            ['conteudo' => '']
        );

        $companies = $user->is_master_admin ? \App\Models\Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/NossaHistoria/Index', [
            'historia' => $historia,
            'companies' => $companies,
            'company' => $company->only(['id', 'nome_fantasia', 'razao_social']),
            'currentCompanyId' => (int) $companyId
        ]);
    }

    public function salvar(SalvarNossaHistoriaRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $companyId = $request->company_id;

            if (!$user->is_master_admin && $user->company_id != $companyId) {
                abort(403, 'Você não tem permissão para salvar nesta empresa.');
            }

            $historia = NossaHistoria::where('company_id', $companyId)->firstOrFail();
            $historia->conteudo = $request->conteudo;
            $historia->save();

            Log::info("Ação Salvar Nossa Historia realizada pelo usuário " . auth()->id());
            DB::commit();
            return redirect()->back()->with('success', 'Nossa História salva com sucesso!');
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
