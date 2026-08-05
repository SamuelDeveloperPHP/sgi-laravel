<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Treinamento;
use App\Models\TreinamentoPresenca;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class TreinamentoPresencaController extends Controller
{
    public function show(Treinamento $treinamento)
    {
        $treinamento->load(['curso', 'local', 'presencas.funcionario', 'instrutor']);
        
        return Inertia::render('HR/Treinamentos/Presenca', [
            'treinamento' => $treinamento
        ]);
    }

    public function addAluno(Treinamento $treinamento, Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => ['required', Rule::exists('rh_funcionarios', 'id')->where('company_id', $treinamento->company_id)]
        ]);

        $treinamento->presencas()->updateOrCreate(
            ['funcionario_id' => $validated['funcionario_id']],
            ['status' => 'Pendente']
        );

        return redirect()->back()->with('success', 'Funcionário adicionado à lista.');
    }

    public function removeAluno(Treinamento $treinamento, Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => ['required', Rule::exists('rh_funcionarios', 'id')->where('company_id', $treinamento->company_id)]
        ]);

        $treinamento->presencas()->where('funcionario_id', $validated['funcionario_id'])->delete();

        return redirect()->back()->with('success', 'Funcionário removido da lista.');
    }

    public function updateStatus(Treinamento $treinamento, Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => ['required', Rule::exists('rh_funcionarios', 'id')->where('company_id', $treinamento->company_id)],
            'status' => 'required|in:Presente,Ausente,Pendente'
        ]);

        $treinamento->presencas()->where('funcionario_id', $validated['funcionario_id'])->update([
            'status' => $validated['status']
        ]);

        return redirect()->back()->with('success', 'Status atualizado com sucesso.');
    }
}
