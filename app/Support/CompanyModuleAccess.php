<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CompanyModuleAccess
{
    public static function defaultEnabledModuleIds(): array
    {
        if (! Schema::hasTable('modules')) {
            return [];
        }

        $query = Module::query()->where('is_active', true);

        if (Schema::hasColumn('modules', 'default_access_policy')) {
            $query->where('default_access_policy', '!=', Module::ACCESS_PRIVATE);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function enabledModuleIdsFor(Company $company): array
    {
        if (! self::canUseCompanyModules()) {
            return self::defaultEnabledModuleIds();
        }

        return $company->modules()
            ->wherePivot('is_enabled', true)
            ->pluck('modules.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function syncDefaultsFor(Company $company): void
    {
        self::syncEnabledFor($company, self::defaultEnabledModuleIds());
    }

    public static function syncEnabledFor(Company $company, array $moduleIds): void
    {
        if (! self::canUseCompanyModules()) {
            return;
        }

        $ids = collect($moduleIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $allowedIds = Module::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->where('default_access_policy', '!=', Module::ACCESS_PRIVATE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $payload = [];
        foreach ($allowedIds as $id) {
            $payload[$id] = ['is_enabled' => true];
        }

        $company->modules()->sync($payload);
    }

    public static function companyCanAccessModule(?int $companyId, Module $module): bool
    {
        if (! $companyId || $module->default_access_policy === Module::ACCESS_PRIVATE) {
            return false;
        }

        if (! self::canUseCompanyModules()) {
            return $module->default_access_policy !== Module::ACCESS_PRIVATE;
        }

        return DB::table('company_module')
            ->where('company_id', $companyId)
            ->where('module_id', $module->id)
            ->where('is_enabled', true)
            ->exists();
    }

    private static function canUseCompanyModules(): bool
    {
        return Schema::hasTable('company_module')
            && Schema::hasTable('companies')
            && Schema::hasTable('modules');
    }
}
