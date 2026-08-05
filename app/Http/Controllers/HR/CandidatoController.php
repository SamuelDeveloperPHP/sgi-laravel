<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use App\Http\Requests\HR\StoreCandidatoRequest;
use App\Http\Requests\HR\UpdateCandidatoRequest;
use Illuminate\Http\Request;

class CandidatoController extends Controller
{
    public function store(StoreCandidatoRequest $request)
    {
        Candidato::create($request->validated());
        return redirect()->back()->with('success', 'Candidato adicionado com sucesso!');
    }

    public function update(UpdateCandidatoRequest $request, Candidato $candidato)
    {
        $candidato->update($request->validated());
        return redirect()->back()->with('success', 'Dados do candidato atualizados com sucesso!');
    }

    public function destroy(Candidato $candidato)
    {
        $candidato->delete();
        return redirect()->back()->with('success', 'Candidato excluído com sucesso!');
    }
}
