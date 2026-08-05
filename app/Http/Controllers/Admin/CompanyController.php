<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCompanyRegistrationRequest;
use App\Models\Company;
use App\Models\CompanyRegistrationReview;
use App\Models\Module;
use App\Notifications\CompanyRegistrationReviewed;
use App\Support\CompanyModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function registrations(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $companies = Company::query()
            ->where('registration_status', 'pending')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('nome_fantasia', 'like', "%{$search}%")
                        ->orWhere('razao_social', 'like', "%{$search}%")
                        ->orWhere('cnpj', 'like', "%{$search}%")
                        ->orWhere('email_administrador', 'like', "%{$search}%");
                });
            })
            ->withCount('users')
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/CompanyRegistrations/Index', [
            'companies' => $companies,
            'filters' => ['search' => $search],
            'pendingCount' => Company::where('registration_status', 'pending')->count(),
        ]);
    }

    public function showRegistration(Company $company)
    {
        $company->load([
            'users:id,company_id,name,email,email_verified_at,created_at',
            'registrationReviews' => fn ($query) => $query
                ->with('reviewer:id,name,email')
                ->latest('created_at'),
        ]);

        return Inertia::render('Admin/CompanyRegistrations/Show', [
            'company' => $company,
        ]);
    }

    public function approveRegistration(ReviewCompanyRegistrationRequest $request, Company $company)
    {
        return $this->reviewRegistration($request, $company, 'approved');
    }

    public function rejectRegistration(ReviewCompanyRegistrationRequest $request, Company $company)
    {
        return $this->reviewRegistration($request, $company, 'rejected');
    }

    private function reviewRegistration(
        ReviewCompanyRegistrationRequest $request,
        Company $company,
        string $decision,
    ) {
        $reason = $request->validated('reason');
        $reviewer = $request->user();

        $reviewedCompany = DB::transaction(function () use ($company, $decision, $reason, $reviewer, $request) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);

            if ($lockedCompany->registration_status !== 'pending') {
                throw ValidationException::withMessages([
                    'company' => 'Este pré-cadastro já foi analisado por outro administrador.',
                ]);
            }

            $approved = $decision === 'approved';
            $lockedCompany->update([
                'status' => $approved,
                'registration_status' => $decision,
                'registration_reviewed_at' => now(),
                'registration_reviewed_by' => $reviewer->id,
                'registration_review_reason' => $reason,
                'cnpj_verificado_em' => $approved
                    ? ($lockedCompany->cnpj_verificado_em ?? now())
                    : $lockedCompany->cnpj_verificado_em,
            ]);

            CompanyRegistrationReview::create([
                'company_id' => $lockedCompany->id,
                'reviewer_id' => $reviewer->id,
                'decision' => $decision,
                'reason' => $reason,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]);

            return $lockedCompany;
        });

        $recipients = $reviewedCompany->users()->get();
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new CompanyRegistrationReviewed($reviewedCompany, $decision, $reason),
            );
        }

        $message = $decision === 'approved'
            ? 'Pré-cadastro aprovado e acesso liberado.'
            : 'Pré-cadastro rejeitado e acesso mantido bloqueado.';

        return redirect()->route('admin.company-registrations.index')->with('success', $message);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Company::query();

        if (Schema::hasTable('company_module')) {
            $query->withCount([
                'modules as enabled_modules_count' => fn ($moduleQuery) => $moduleQuery
                    ->wherePivot('is_enabled', true),
            ]);
        }

        if ($search) {
            $query->where('nome_fantasia', 'like', "%{$search}%")
                ->orWhere('razao_social', 'like', "%{$search}%")
                ->orWhere('cnpj', 'like', "%{$search}%");
        }

        $companies = $query->orderBy('nome_fantasia')->paginate(10)->withQueryString();

        $activeCount = Company::where('status', true)->count();
        $inactiveCount = Company::where('status', false)->count();
        $pendingCount = Company::where('registration_status', 'pending')->count();

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only('search'),
            'metrics' => [
                'active' => $activeCount,
                'inactive' => $inactiveCount,
                'pending' => $pendingCount,
                'total' => $activeCount + $inactiveCount,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => new Company,
            'isEdit' => false,
            'modules' => $this->moduleOptions(),
            'enabledModuleIds' => CompanyModuleAccess::defaultEnabledModuleIds(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_fantasia' => 'required|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:20|unique:companies,cnpj',
            'status' => 'boolean',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'email_corporativo' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'nome_administrador' => 'nullable|string|max:255',
            'email_administrador' => 'nullable|email|max:255',
            'observacoes' => 'nullable|string',
            'module_ids' => 'nullable|array',
            'module_ids.*' => [
                'integer',
                Rule::exists('modules', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->where('default_access_policy', '!=', Module::ACCESS_PRIVATE)),
            ],
        ]);

        $moduleIds = $validated['module_ids'] ?? CompanyModuleAccess::defaultEnabledModuleIds();
        unset($validated['module_ids']);

        DB::transaction(function () use ($validated, $moduleIds) {
            $company = Company::create($validated);
            CompanyModuleAccess::syncEnabledFor($company, $moduleIds);
        });

        return redirect()->route('admin.companies.index')->with('message', 'Empresa criada com sucesso!');
    }

    public function edit(Company $company)
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => $company,
            'isEdit' => true,
            'modules' => $this->moduleOptions(),
            'enabledModuleIds' => CompanyModuleAccess::enabledModuleIdsFor($company),
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $syncModules = $request->exists('module_ids');

        $validated = $request->validate([
            'nome_fantasia' => 'required|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:20|unique:companies,cnpj,'.$company->id,
            'status' => 'boolean',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'email_corporativo' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'nome_administrador' => 'nullable|string|max:255',
            'email_administrador' => 'nullable|email|max:255',
            'observacoes' => 'nullable|string',
            'module_ids' => 'nullable|array',
            'module_ids.*' => [
                'integer',
                Rule::exists('modules', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->where('default_access_policy', '!=', Module::ACCESS_PRIVATE)),
            ],
        ]);

        $moduleIds = $validated['module_ids'] ?? [];
        unset($validated['module_ids']);

        DB::transaction(function () use ($company, $validated, $syncModules, $moduleIds) {
            $company->update($validated);

            if ($syncModules) {
                CompanyModuleAccess::syncEnabledFor($company, $moduleIds);
            }
        });

        return redirect()->route('admin.companies.index')->with('message', 'Empresa atualizada com sucesso!');
    }

    private function moduleOptions(): array
    {
        if (! Schema::hasTable('modules')) {
            return [];
        }

        return Module::with(['children' => fn ($query) => $query->orderBy('order')])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(fn (Module $module) => $this->formatModuleOption($module))
            ->values()
            ->all();
    }

    private function formatModuleOption(Module $module): array
    {
        return [
            'id' => $module->id,
            'name' => $module->name,
            'slug' => $module->slug,
            'is_active' => (bool) $module->is_active,
            'is_private' => $module->default_access_policy === Module::ACCESS_PRIVATE,
            'default_access_policy' => $module->default_access_policy,
            'children' => $module->children
                ->map(fn (Module $child) => $this->formatModuleOption($child))
                ->values()
                ->all(),
        ];
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('admin.companies.index')->with('message', 'Empresa excluída com sucesso!');
    }
}
