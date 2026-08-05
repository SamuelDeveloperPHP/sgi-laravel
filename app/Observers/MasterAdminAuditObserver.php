<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Observer que registra na tabela master_admin_audit_log todas as
 * operações de escrita (create/update/delete/restore/forceDelete)
 * realizadas por usuários com is_master_admin = true.
 *
 * Operações de outros usuários NÃO são logadas aqui (são naturalmente
 * limitadas pelo TenantScope e pelas Policies). A justificativa para
 * auditar apenas master admin é que esse perfil bypassa o tenant
 * scope e pode acidentalmente (ou maliciosamente) afetar dados de
 * outras empresas — exigindo rastreabilidade absoluta.
 *
 * Registrado em AppServiceProvider::boot() para os models Tenantable.
 */
class MasterAdminAuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        // Em models com SoftDeletes, este event dispara em soft-delete.
        // Em models sem SoftDeletes, dispara em hard-delete.
        $this->log($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored');
    }

    public function forceDeleted(Model $model): void
    {
        $this->log($model, 'forceDeleted');
    }

    /**
     * Loga a operação se (e somente se) o usuário autenticado for
     * master admin. Falhas no log são silenciadas (não devem quebrar
     * a operação de negócio principal).
     */
    private function log(Model $model, string $ability): void
    {
        // Sem auth (console, jobs, seeders) → não loga.
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();
        if (! $user->is_master_admin) {
            return;
        }

        try {
            DB::table('master_admin_audit_log')->insert([
                'user_id' => $user->id,
                'company_id_target' => $model instanceof Company
                    ? $model->getKey()
                    : ($model->company_id ?? null),
                'ability' => $ability,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'changes_json' => json_encode([
                    'before' => $ability === 'created' ? null : $model->getOriginal(),
                    'after' => in_array($ability, ['deleted', 'forceDeleted'], true)
                        ? null
                        : $model->getAttributes(),
                    'dirty' => $model->getChanges(),
                ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Não quebrar a operação principal por falha no log.
            // TODO: encaminhar para canal de log estruturado (Sentry, etc.).
            \Log::warning('MasterAdminAuditObserver: falha ao gravar audit log', [
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'ability' => $ability,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
