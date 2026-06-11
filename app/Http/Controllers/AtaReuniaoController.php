<?php

namespace App\Http\Controllers;

use App\Models\AtaReuniao;
use App\Models\AtaParticipante;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\AtaSolicitacaoAssinaturaMail;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreAtaReuniaoRequest;
use App\Http\Requests\UpdateAtaReuniaoRequest;
use App\Http\Requests\DestroyAtaReuniaoRequest;
use App\Http\Requests\SolicitarAssinaturasAtaReuniaoRequest;

class AtaReuniaoController extends Controller
{


    public function index(Request $request)
    {
        $this->authorizePermission('view-atas-reuniao');

        $user = auth()->user();
        
        $query = AtaReuniao::with(['responsavel', 'participantes.user']);

        if ($user->is_master_admin) {
            $companyId = $request->input('company_id', Company::first()->id ?? null);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        $atas = $query->orderBy('created_at', 'desc')->get()->map(function ($ata) {
            $totalParticipantes = $ata->participantes->count();
            $assinados = $ata->participantes->where('assinado', true)->count();
            
            $ata->status_assinaturas = $totalParticipantes > 0 ? "{$assinados} de {$totalParticipantes}" : "Sem participantes";
            return $ata;
        });

        $companies = $user->is_master_admin ? Company::all(['id', 'nome_fantasia']) : [];

        return Inertia::render('ISO9001/Atas/Index', [
            'atas' => $atas,
            'companies' => $companies,
            'currentCompanyId' => (int) ($companyId ?? $user->company_id)
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('manage-atas-reuniao');
        
        $user = auth()->user();
        $companyId = $user->is_master_admin ? $request->input('company_id') : $user->company_id;

        if (!$companyId) {
            abort(400, 'Empresa não identificada.');
        }

        $users = User::where('company_id', $companyId)->get(['id', 'name']);

        return Inertia::render('ISO9001/Atas/Form', [
            'ata' => null,
            'users' => $users,
            'companyId' => (int) $companyId
        ]);
    }

    public function store(StoreAtaReuniaoRequest $request)
    {
        DB::beginTransaction();
        try {
            $ata = new AtaReuniao($request->only([
                'company_id', 'data', 'hora_inicio', 'hora_termino', 'local', 'assunto', 'pautas', 'registro'
            ]));
            
            $ata->responsavel_id = auth()->id();
            $ata->status = 'rascunho';
            $ata->save();

            if ($request->has('participantes')) {
                foreach ($request->participantes as $userId) {
                    AtaParticipante::create([
                        'ata_id' => $ata->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            Log::info("Ação store realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('atas-reuniao.index')->with('success', 'Ata criada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function edit(AtaReuniao $ata)
    {
        $this->authorizePermission('manage-atas-reuniao');

        if ($ata->status !== 'rascunho') {
            abort(403, 'Apenas atas em rascunho podem ser editadas.');
        }

        $ata->load('participantes');
        $participantesIds = $ata->participantes->pluck('user_id')->toArray();

        $users = User::where('company_id', $ata->company_id)->get(['id', 'name']);

        return Inertia::render('ISO9001/Atas/Form', [
            'ata' => $ata,
            'participantesIds' => $participantesIds,
            'users' => $users,
            'companyId' => $ata->company_id
        ]);
    }

    public function update(UpdateAtaReuniaoRequest $request, AtaReuniao $ata)
    {
        if ($ata->status !== 'rascunho') {
            abort(403, 'Apenas atas em rascunho podem ser editadas.');
        }

        DB::beginTransaction();
        try {
            $ata->update($request->only([
                'data', 'hora_inicio', 'hora_termino', 'local', 'assunto', 'pautas', 'registro'
            ]));

            $ata->user_edit = auth()->id();
            $ata->save();

            // Sync participantes
            $ata->participantes()->delete();
            if ($request->has('participantes')) {
                foreach ($request->participantes as $userId) {
                    AtaParticipante::create([
                        'ata_id' => $ata->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            Log::info("Ação update realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->route('atas-reuniao.index')->with('success', 'Ata atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function solicitarAssinaturas(SolicitarAssinaturasAtaReuniaoRequest $request, AtaReuniao $ata)
    {
        if ($ata->status !== 'rascunho') {
            abort(403, 'Apenas rascunhos podem ser enviados para assinatura.');
        }

        DB::beginTransaction();
        try {
            $ata->status = 'aguardando_assinaturas';
            $ata->save();

            $participantes = $ata->participantes()->with('user')->get();

            foreach ($participantes as $participante) {
                Mail::to($participante->user->email)->send(new AtaSolicitacaoAssinaturaMail($ata, $participante->user));
            }

            Log::info("Ação solicitarAssinaturas realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'E-mails enviados aos participantes!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function show(AtaReuniao $ata)
    {
        $ata->load(['empresa', 'responsavel', 'participantes.user']);
        
        $user = auth()->user();
        
        // Verifica se o usuario tem permissao ou e participante
        $isParticipante = $ata->participantes->contains('user_id', $user->id);
        
        if (!$isParticipante && !$user->hasPermissionTo('view-atas-reuniao')) {
            abort(403, 'Você não tem acesso a esta ata.');
        }

        $meuStatus = $ata->participantes->where('user_id', $user->id)->first();

        return Inertia::render('ISO9001/Atas/Show', [
            'ata' => $ata,
            'meuStatus' => $meuStatus
        ]);
    }

    public function assinar(AtaReuniao $ata)
    {
        $user = auth()->user();
        
        $participante = AtaParticipante::where('ata_id', $ata->id)->where('user_id', $user->id)->firstOrFail();

        if ($participante->assinado) {
            return redirect()->back()->with('error', 'Você já assinou esta ata.');
        }

        $participante->assinado = true;
        $participante->data_assinatura = now();
        $participante->hash_assinatura = hash('sha256', $ata->id . $user->id . now() . Str::random(10));
        $participante->save();

        // Checar se todos assinaram
        $total = $ata->participantes()->count();
        $assinados = $ata->participantes()->where('assinado', true)->count();

        if ($total > 0 && $total === $assinados) {
            $ata->status = 'concluida';
            $ata->save();
        }

        return redirect()->back()->with('success', 'Ata assinada com sucesso!');
    }

    public function destroy(DestroyAtaReuniaoRequest $request, AtaReuniao $ata)
    {
        DB::beginTransaction();
        try {
            $ata->delete();
            Log::info("Ação destroy realizada pelo usuário " . auth()->user()->id);
            DB::commit();
            return redirect()->back()->with('success', 'Ata excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error($e->getMessage());
            return back()->with('error', 'Erro interno ao realizar operação.');
        }
    }

    public function exportarPdf(AtaReuniao $ata)
    {
        $this->authorizePermission('view-atas-reuniao');
        $ata->load(['empresa', 'responsavel', 'participantes.user']);

        $pdf = Pdf::loadView('pdf.ata_reuniao', ['ata' => $ata]);
        
        return $pdf->download("Ata_Reuniao_{$ata->data->format('d_m_Y')}.pdf");
    }
}
