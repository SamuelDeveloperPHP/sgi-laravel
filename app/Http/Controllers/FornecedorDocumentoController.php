<?php

namespace App\Http\Controllers;

use App\Models\FornecedorDocumento;
use App\Models\Fornecedor;
use App\Mail\DocumentoReprovadoMail;
use App\Http\Requests\StoreFornecedorDocumentoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FornecedorDocumentoController extends Controller
{
    public function store(StoreFornecedorDocumentoRequest $request, Fornecedor $fornecedor)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $path = $request->file('arquivo')->store('fornecedores/documentos', 'public');

            $documento = $fornecedor->documentos()->create([
                'nome_documento' => $data['nome_documento'],
                'arquivo' => $path,
                'data_validade' => $data['data_validade'] ?? null,
                'status_aprovacao' => 'pendente'
            ]);

            Log::info("Usuário " . auth()->id() . " fez upload do documento ID: {$documento->id} para fornecedor ID: {$fornecedor->id}");
            DB::commit();

            return redirect()->back()->with('success', 'Documento adicionado!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao adicionar documento para fornecedor ID {$fornecedor->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao salvar documento. Tente novamente.');
        }
    }

    public function download(FornecedorDocumento $documento)
    {
        if (!auth()->user()->hasPermissionTo('view-fornecedores')) {
            abort(403);
        }
        
        return Storage::disk('public')->response($documento->arquivo);
    }

    public function avaliar(Request $request, FornecedorDocumento $documento)
    {
        if (!auth()->user()->hasPermissionTo('manage-fornecedores')) {
            abort(403);
        }

        $request->validate([
            'status_aprovacao' => 'required|in:aprovado,reprovado',
            'motivo_reprovacao' => 'required_if:status_aprovacao,reprovado|nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $documento->update([
                'status_aprovacao' => $request->status_aprovacao,
                'motivo_reprovacao' => $request->status_aprovacao === 'reprovado' ? $request->motivo_reprovacao : null,
                'avaliador_id' => auth()->id(),
                'data_aprovacao' => Carbon::now()
            ]);

            $this->atualizarStatusHomologacaoFornecedor($documento->fornecedor);

            Log::info("Usuário " . auth()->id() . " avaliou documento ID: {$documento->id} como {$request->status_aprovacao}");
            
            DB::commit();

            if ($request->status_aprovacao === 'reprovado' && $documento->fornecedor->email) {
                try {
                    Mail::to($documento->fornecedor->email)->send(new DocumentoReprovadoMail($documento));
                } catch (\Exception $e) {
                    Log::error("Erro ao enviar e-mail de reprovação do documento {$documento->id}: " . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', 'Avaliação do documento registrada!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao avaliar documento ID {$documento->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao avaliar documento. Tente novamente.');
        }
    }

    public function destroy(FornecedorDocumento $documento)
    {
        if (!auth()->user()->hasPermissionTo('manage-fornecedores')) {
            abort(403);
        }

        Storage::disk('public')->delete($documento->arquivo);
        $documento->delete();

        return redirect()->back()->with('success', 'Documento removido.');
    }
}
