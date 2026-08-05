<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_module')) {
            Schema::create('company_module', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('module_id');
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
                $table->index('company_id');
                $table->index('module_id');
                $table->unique(['company_id', 'module_id']);
            });
        }

        $this->ensureIndexes();
        $this->backfillExistingCompanies();
    }

    public function down(): void
    {
        Schema::dropIfExists('company_module');
    }

    private function backfillExistingCompanies(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasTable('modules') || ! Schema::hasTable('company_module')) {
            return;
        }

        $moduleIds = DB::table('modules')
            ->where('is_active', true)
            ->where('default_access_policy', '!=', 'private')
            ->pluck('id')
            ->all();

        if ($moduleIds === []) {
            return;
        }

        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($companies) use ($moduleIds) {
                $now = now();
                $rows = [];

                foreach ($companies as $company) {
                    foreach ($moduleIds as $moduleId) {
                        $rows[] = [
                            'company_id' => $company->id,
                            'module_id' => $moduleId,
                            'is_enabled' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                foreach (array_chunk($rows, 1000) as $chunk) {
                    DB::table('company_module')->insertOrIgnore($chunk);
                }
            });
    }

    private function ensureIndexes(): void
    {
        if (! Schema::hasTable('company_module')) {
            return;
        }

        $hasModuleIndex = $this->hasIndex('company_module_module_id_index');
        $hasUniqueIndex = $this->hasIndex('company_module_company_id_module_id_unique');

        Schema::table('company_module', function (Blueprint $table) use ($hasModuleIndex, $hasUniqueIndex) {
            if (! $hasModuleIndex) {
                $table->index('module_id');
            }

            if (! $hasUniqueIndex) {
                $table->unique(['company_id', 'module_id']);
            }
        });
    }

    private function hasIndex(string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM company_module WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
