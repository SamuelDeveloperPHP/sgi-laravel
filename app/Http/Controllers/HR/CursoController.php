<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Http\Requests\StoreCursoRequest;
use App\Http\Requests\UpdateCursoRequest;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class CursoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Curso::class);

        $cursos = Curso::orderBy('nome')->paginate(15);

        return Inertia::render('HR/Cursos/Index', [
            'cursos' => $cursos
        ]);
    }

    public function store(StoreCursoRequest $request): RedirectResponse
    {
        $this->authorize('create', Curso::class);

        Curso::create($request->validated());

        return redirect()->back()->with('message', 'Curso cadastrado com sucesso!');
    }

    public function update(UpdateCursoRequest $request, Curso $curso): RedirectResponse
    {
        $this->authorize('update', $curso);

        $curso->update($request->validated());

        return redirect()->back()->with('message', 'Curso atualizado com sucesso!');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        $this->authorize('delete', $curso);

        $curso->delete();

        return redirect()->back()->with('message', 'Curso excluído com sucesso!');
    }
}
