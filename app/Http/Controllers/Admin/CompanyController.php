<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('nome_fantasia')->paginate(10);
        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => new Company(),
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_fantasia' => 'required|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:20|unique:companies,cnpj',
            'status' => 'boolean',
        ]);

        Company::create($validated);

        return redirect()->route('admin.companies.index')->with('message', 'Empresa criada com sucesso!');
    }

    public function edit(Company $company)
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => $company,
            'isEdit' => true
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'nome_fantasia' => 'required|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:20|unique:companies,cnpj,' . $company->id,
            'status' => 'boolean',
        ]);

        $company->update($validated);

        return redirect()->route('admin.companies.index')->with('message', 'Empresa atualizada com sucesso!');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('admin.companies.index')->with('message', 'Empresa excluída com sucesso!');
    }
}
