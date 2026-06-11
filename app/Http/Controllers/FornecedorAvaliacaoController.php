<?php

namespace App\Http\Controllers;

use App\Models\FornecedorAvaliacao;
use App\Models\Fornecedor;
use App\Http\Requests\StoreFornecedorAvaliacaoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FornecedorAvaliacaoController extends Controller
{
    public function store(StoreFornecedorAvaliacaoRequest $request, Fornecedor $fornecedor)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            $somaNotas = 0;
            foreach ($data['criterios'] as $c) {
                $somaNotas += (float)$c['nota'];
            }
            $media = count($data['criterios']) > 0 ? ($somaNotas / count($data['criterios'])) : 0;

            $avaliacao = $fornecedor->avaliacoes()->create([
                'data_avaliacao' => Carbon::now(),
                'avaliador_id' => auth()->id(),
                'criterios' => $data['criterios'],
                'nota_geral' => number_format($media, 2, '.', ''),
                'observacoes' => $data['observacoes'] ?? null
            ]);

            if (isset($data['salvar_como_padrao']) && $data['salvar_como_padrao']) {
                $company = $fornecedor->empresa;
                if ($company) {
                    $novosPadroes = array_map(function($c) {
                        return ['nome' => $c['nome']];
                    }, $data['criterios']);
                    $company->criterios_avaliacao_fornecedor = $novosPadroes;
                    $company->save();
                    Log::info("Usuário " . auth()->id() . " atualizou os critérios padrão da empresa ID: {$company->id}");
                }
            }

            $this->atualizarIdfFornecedor($fornecedor);

            Log::info("Usuário " . auth()->id() . " criou avaliação ID: {$avaliacao->id} para fornecedor ID: {$fornecedor->id}");
            DB::commit();

            return redirect()->back()->with('success', 'Avaliação registrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar avaliação para fornecedor ID {$fornecedor->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno ao registrar avaliação. Tente novamente.');
        }
    }

    private function atualizarIdfFornecedor(Fornecedor $fornecedor)
    {
        $mediaHistorica = $fornecedor->avaliacoes()->avg('nota_geral');
        
        $fornecedor->update([
            'idf_atual' => $mediaHistorica ?? 0
        ]);
    }
}
