<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Company;
use App\Http\Requests\StoreBeneficioRequest;
use App\Http\Requests\UpdateBeneficioRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BeneficioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Beneficio::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;
        
        $beneficios = Beneficio::when($companyId, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->paginate(10);

        $companies = auth()->user()->is_master_admin ? Company::all() : [];

        return Inertia::render('HR/Beneficios/Index', [
            'beneficios' => $beneficios,
            'companies' => $companies,
            'filters' => $request->only(['company_id']),
        ]);
    }

    public function store(StoreBeneficioRequest $request)
    {
        $this->authorize('create', Beneficio::class);
        Beneficio::create($request->validated());

        return redirect()->route('admin.hr.beneficios.index')->with('success', 'Benefício cadastrado com sucesso.');
    }

    public function update(UpdateBeneficioRequest $request, Beneficio $beneficio)
    {
        $this->authorize('update', $beneficio);
        $beneficio->update($request->validated());

        return redirect()->route('admin.hr.beneficios.index')->with('success', 'Benefício atualizado com sucesso.');
    }

    public function destroy(Beneficio $beneficio)
    {
        $this->authorize('delete', $beneficio);
        $beneficio->delete();
        return redirect()->route('admin.hr.beneficios.index')->with('success', 'Benefício excluído com sucesso.');
    }
}
