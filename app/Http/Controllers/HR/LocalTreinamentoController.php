<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LocalTreinamento;
use App\Http\Requests\StoreLocalTreinamentoRequest;
use App\Http\Requests\UpdateLocalTreinamentoRequest;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class LocalTreinamentoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', LocalTreinamento::class);

        $locais = LocalTreinamento::orderBy('nome')->paginate(15);

        return Inertia::render('HR/LocaisTreinamento/Index', [
            'locais' => $locais
        ]);
    }

    public function store(StoreLocalTreinamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', LocalTreinamento::class);

        LocalTreinamento::create($request->validated());

        return redirect()->back()->with('message', 'Local cadastrado com sucesso!');
    }

    public function update(UpdateLocalTreinamentoRequest $request, LocalTreinamento $localTreinamento): RedirectResponse
    {
        $this->authorize('update', $localTreinamento);

        $localTreinamento->update($request->validated());

        return redirect()->back()->with('message', 'Local atualizado com sucesso!');
    }

    public function destroy(LocalTreinamento $localTreinamento): RedirectResponse
    {
        $this->authorize('delete', $localTreinamento);

        $localTreinamento->delete();

        return redirect()->back()->with('message', 'Local excluído com sucesso!');
    }
}
