<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Treinamento;
use App\Models\Curso;
use App\Models\LocalTreinamento;
use App\Models\Funcionario;
use App\Models\TreinamentoPresenca;
use App\Http\Requests\StoreTreinamentoRequest;
use App\Http\Requests\UpdateTreinamentoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class TreinamentoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Treinamento::class);

        $treinamentos = Treinamento::with(['curso', 'local'])->orderByDesc('data_inicio')->paginate(15);
        $cursos = Curso::orderBy('nome')->get();
        $locais = LocalTreinamento::orderBy('nome')->get();

        return Inertia::render('HR/Treinamentos/Index', [
            'treinamentos' => $treinamentos,
            'cursos' => $cursos,
            'locais' => $locais
        ]);
    }

    public function store(StoreTreinamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', Treinamento::class);

        Treinamento::create($request->validated());

        return redirect()->back()->with('message', 'Treinamento criado com sucesso!');
    }

    public function update(UpdateTreinamentoRequest $request, Treinamento $treinamento): RedirectResponse
    {
        $this->authorize('update', $treinamento);

        $treinamento->update($request->validated());

        return redirect()->back()->with('message', 'Treinamento atualizado com sucesso!');
    }

    public function destroy(Treinamento $treinamento): RedirectResponse
    {
        $this->authorize('delete', $treinamento);

        $treinamento->delete();

        return redirect()->back()->with('message', 'Treinamento excluído com sucesso!');
    }

    // Gerenciamento de Presença (Alunos)
    public function presencas(Treinamento $treinamento)
    {
        $this->authorize('view', $treinamento);

        $treinamento->load(['curso', 'local', 'presencas.funcionario']);
        $funcionarios = Funcionario::orderBy('nome')->get();

        return Inertia::render('HR/Treinamentos/Presenca', [
            'treinamento' => $treinamento,
            'funcionarios' => $funcionarios
        ]);
    }

    public function addAluno(Request $request, Treinamento $treinamento)
    {
        $this->authorize('update', $treinamento);
        
        $request->validate([
            'funcionario_id' => ['required', Rule::exists('rh_funcionarios', 'id')->where('company_id', $treinamento->company_id)]
        ]);

        TreinamentoPresenca::firstOrCreate([
            'treinamento_id' => $treinamento->id,
            'funcionario_id' => $request->funcionario_id
        ], [
            'presente' => true // default as present when manually added
        ]);

        return redirect()->back()->with('message', 'Aluno adicionado à turma.');
    }

    public function removeAluno(Treinamento $treinamento, $presencaId)
    {
        $this->authorize('update', $treinamento);
        
        TreinamentoPresenca::where('id', $presencaId)->where('treinamento_id', $treinamento->id)->delete();

        return redirect()->back()->with('message', 'Aluno removido da turma.');
    }

    public function togglePresenca(Request $request, Treinamento $treinamento, $presencaId)
    {
        $this->authorize('update', $treinamento);
        
        $request->validate([
            'presente' => 'required|boolean'
        ]);

        TreinamentoPresenca::where('id', $presencaId)->where('treinamento_id', $treinamento->id)->update([
            'presente' => $request->presente
        ]);

        return redirect()->back();
    }
}
