<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que bloqueia acesso aos módulos do sistema enquanto o
 * usuário não houver concluído o onboarding (cadastro da empresa
 * com CNPJ válido).
 *
 * Regra de negócio confirmada pelo usuário (memória do projeto
 * sgi-laravel-access-rules):
 *
 *   "outros usuários/ novo usuário, inicialmente deve cadastrar uma
 *    empresa com CNPJ válido para ter acesso aos demais módulos,
 *    assim que for cadastrada a empresa para o usuário ele não pode
 *    ter acesso aos demais dados CNPJ nem por rotas e urls de outras
 *    empresas."
 *
 * Comportamento:
 *
 *   - Master admin (is_master_admin = true): SEMPRE passa, acessa
 *     tudo independente de ter ou não company_id.
 *
 *   - Usuário comum com company_id válido: passa normalmente. A
 *     partir daí, todas as outras camadas (TenantScope, Policies,
 *     FormRequests, FKs) garantem isolamento entre empresas.
 *
 *   - Usuário comum SEM company_id: redirecionado para
 *     /onboarding/company. Exceções: rotas do próprio onboarding,
 *     logout e endpoints internos de notification/verify-email.
 *
 *   - Não autenticado: passa adiante (o middleware 'auth' deve ter
 *     barrado antes deste).
 *
 * IMPORTANTE — registrar como alias 'company.required' em
 * bootstrap/app.php e aplicar a TODAS as rotas dos módulos
 * (auditorias, projetos, NCs, ISO 9001, admin, etc).
 */
class RequireCompany
{
    /**
     * Rotas (ou padrões) que NÃO disparam o redirect mesmo sem company.
     * O usuário precisa conseguir concluir onboarding, deslogar, e
     * gerenciar verificação de email mesmo nesse estado.
     */
    private const ALLOWED_ROUTES = [
        'onboarding.*',
        'logout',
        'verification.*',
        'password.confirm',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sem usuário autenticado: deixa o middleware 'auth' lidar
        if (! $user) {
            return $next($request);
        }

        // Master admin tem acesso global irrestrito (memória:
        // sgi-laravel-access-rules item 1)
        if ($user->is_master_admin) {
            return $next($request);
        }

        if (ModuleAccess::expirePublicAccountIfNeeded($user, true)) {
            auth()->guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Seu cadastro publico temporario expirou e foi bloqueado.');
        }

        if ($user->company_id
            && (! $user->company?->status || $user->company?->registration_status !== 'approved')) {
            if ($request->routeIs('onboarding.pending')) {
                return $next($request);
            }

            return redirect()->route('onboarding.pending')
                ->with('info', 'Seu pré-cadastro está em análise.');
        }

        // Já tem empresa ativa: segue normalmente — TenantScope cuida
        // do isolamento daqui pra frente
        if ($user->company_id) {
            return $next($request);
        }

        // Permite finalizar onboarding, deslogar, verificar email
        foreach (self::ALLOWED_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Sem empresa cadastrada — força o onboarding antes de
        // qualquer outra navegação
        return redirect()->route('onboarding.company')
            ->with('info', 'Conclua o cadastro da sua empresa para acessar o sistema.');
    }
}
