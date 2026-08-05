<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\TreinamentoMeta;
use App\Http\Requests\StoreTreinamentoMetaRequest;
use App\Http\Requests\UpdateTreinamentoMetaRequest;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class TreinamentoMetaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TreinamentoMeta::class);

        $metas = TreinamentoMeta::orderByDesc('ano')->paginate(15);

        return Inertia::render('HR/TreinamentoMetas/Index', [
            'metas' => $metas
        ]);
    }

    public function store(StoreTreinamentoMetaRequest $request): RedirectResponse
    {
        $this->authorize('create', TreinamentoMeta::class);

        TreinamentoMeta::create($request->validated());

        return redirect()->back()->with('message', 'Meta cadastrada com sucesso!');
    }

    public function update(UpdateTreinamentoMetaRequest $request, TreinamentoMeta $treinamentoMeta): RedirectResponse
    {
        $this->authorize('update', $treinamentoMeta);

        $treinamentoMeta->update($request->validated());

        return redirect()->back()->with('message', 'Meta atualizada com sucesso!');
    }

    public function destroy(TreinamentoMeta $treinamentoMeta): RedirectResponse
    {
        $this->authorize('delete', $treinamentoMeta);

        $treinamentoMeta->delete();

        return redirect()->back()->with('message', 'Meta excluída com sucesso!');
    }
}
