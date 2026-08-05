<?php

namespace App\Console\Commands;

use App\Support\AuthorizationPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckProductionSecurity extends Command
{
    protected $signature = 'security:check-production';

    protected $description = 'Falha se as configurações essenciais de produção não forem seguras';

    public function handle(): int
    {
        [$permissionsReady, $rolesReady, $authorizationDetail] = $this->authorizationState();

        $checks = [
            ['APP_ENV deve ser production', app()->environment('production')],
            ['APP_DEBUG deve estar desativado', config('app.debug') === false],
            ['APP_URL deve usar HTTPS', str_starts_with((string) config('app.url'), 'https://')],
            ['SESSION_SECURE_COOKIE deve estar ativado', config('session.secure') === true],
            ['SESSION_HTTP_ONLY deve estar ativado', config('session.http_only') === true],
            ['SESSION_ENCRYPT deve estar ativado', config('session.encrypt') === true],
            ['SESSION_SAME_SITE deve ser lax ou strict', in_array(config('session.same_site'), ['lax', 'strict'], true)],
            ['Todas as permissions obrigatorias devem existir', $permissionsReady],
            ['Todos os papeis de negocio devem existir', $rolesReady],
        ];

        $failed = false;
        foreach ($checks as [$label, $passed]) {
            $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ').$label);
            $failed = $failed || ! $passed;
        }

        if ($authorizationDetail !== '') {
            $this->warn($authorizationDetail);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function authorizationState(): array
    {
        try {
            if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
                return [false, false, 'Tabelas de autorizacao ausentes. Execute as migrations e os seeders.'];
            }

            $missingPermissions = array_values(array_diff(
                AuthorizationPermissions::all(),
                DB::table('permissions')->where('guard_name', 'web')->pluck('name')->all(),
            ));
            $missingRoles = array_values(array_diff(
                AuthorizationPermissions::BUSINESS_ROLES,
                DB::table('roles')->where('guard_name', 'web')->pluck('name')->all(),
            ));

            $details = [];
            if ($missingPermissions !== []) {
                $details[] = 'Permissions ausentes: '.implode(', ', $missingPermissions);
            }
            if ($missingRoles !== []) {
                $details[] = 'Papeis ausentes: '.implode(', ', $missingRoles);
            }

            return [$missingPermissions === [], $missingRoles === [], implode(PHP_EOL, $details)];
        } catch (\Throwable $exception) {
            return [false, false, 'Nao foi possivel validar a autorizacao no banco: '.$exception->getMessage()];
        }
    }
}
