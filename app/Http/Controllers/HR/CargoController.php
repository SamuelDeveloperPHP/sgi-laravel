<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Company;
use App\Http\Requests\StoreCargoRequest;
use App\Http\Requests\UpdateCargoRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Cargo::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;
        
        $cargos = Cargo::when($companyId, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->paginate(10);

        $companies = auth()->user()->is_master_admin ? Company::all() : [];

        return Inertia::render('HR/Cargos/Index', [
            'cargos' => $cargos,
            'companies' => $companies,
            'filters' => $request->only(['company_id']),
        ]);
    }

    public function store(StoreCargoRequest $request)
    {
        $this->authorize('create', Cargo::class);
        Cargo::create($request->validated());

        return redirect()->route('admin.hr.cargos.index')->with('success', 'Cargo cadastrado com sucesso.');
    }

    public function update(UpdateCargoRequest $request, Cargo $cargo)
    {
        $this->authorize('update', $cargo);
        $cargo->update($request->validated());

        return redirect()->route('admin.hr.cargos.index')->with('success', 'Cargo atualizado com sucesso.');
    }

    public function destroy(Cargo $cargo)
    {
        $this->authorize('delete', $cargo);
        $cargo->delete();
        return redirect()->route('admin.hr.cargos.index')->with('success', 'Cargo excluído com sucesso.');
    }
}
