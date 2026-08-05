<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Company;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Area::class);
        $companyId = auth()->user()->is_master_admin ? $request->company_id : auth()->user()->company_id;
        
        $areas = Area::when($companyId, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->paginate(10);

        $companies = auth()->user()->is_master_admin ? Company::all() : [];

        return Inertia::render('HR/Areas/Index', [
            'areas' => $areas,
            'companies' => $companies,
            'filters' => $request->only(['company_id']),
        ]);
    }

    public function store(StoreAreaRequest $request)
    {
        $this->authorize('create', Area::class);
        Area::create($request->validated());

        return redirect()->route('admin.hr.areas.index')->with('success', 'Área cadastrada com sucesso.');
    }

    public function update(UpdateAreaRequest $request, Area $area)
    {
        $this->authorize('update', $area);
        $area->update($request->validated());

        return redirect()->route('admin.hr.areas.index')->with('success', 'Área atualizada com sucesso.');
    }

    public function destroy(Area $area)
    {
        $this->authorize('delete', $area);
        $area->delete();
        return redirect()->route('admin.hr.areas.index')->with('success', 'Área excluída com sucesso.');
    }
}
