<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditoriaRequest;
use App\Http\Requests\UpdateAuditoriaRequest;
use App\Http\Requests\DestroyAuditoriaRequest;
use App\Models\AuditoriaInterna;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditoriaController extends Controller
{
    public function __construct()
    {
        // Wire automatic Policy checks (AuditoriaInternaPolicy@viewAny/view/create/update/delete)
        // para todas as actions RESTful. Segunda camada além do TenantScope.
        $this->authorizeResource(AuditoriaInterna::class, 'auditoria');
    }

    public function index()
    {
        $auditorias = AuditoriaInterna::orderBy('created', 'desc')->paginate(10);
        return Inertia::render('Auditorias/Index', [
            'auditorias' => $auditorias
        ]);
    }

    public function create()
    {
        return Inertia::render('Auditorias/Form', [
            'auditoria' => new AuditoriaInterna(),
            'isEdit' => false
        ]);
    }

    public function store(StoreAuditoriaRequest $request)
    {
        DB::beginTransaction();
        try {
            AuditoriaInterna::create($request->validated());

            Log::info("Ação store realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('auditorias.index')->with('message', 'Auditoria cadastrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show(AuditoriaInterna $auditoria)
    {
        return Inertia::render('Auditorias/Show', [
            'auditoria' => $auditoria
        ]);
    }

    public function edit(AuditoriaInterna $auditoria)
    {
        return Inertia::render('Auditorias/Form', [
            'auditoria' => $auditoria,
            'isEdit' => true
        ]);
    }

    public function update(UpdateAuditoriaRequest $request, AuditoriaInterna $auditoria)
    {
        DB::beginTransaction();
        try {
            $auditoria->update($request->validated());

            Log::info("Ação update realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('auditorias.index')->with('message', 'Auditoria atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function destroy(DestroyAuditoriaRequest $request, AuditoriaInterna $auditoria)
    {
        DB::beginTransaction();
        try {
            $auditoria->delete();
            Log::info("Ação destroy realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('auditorias.index')->with('message', 'Auditoria excluída com sucesso!');
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
