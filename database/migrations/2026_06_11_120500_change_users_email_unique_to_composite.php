<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Muda a constraint UNIQUE de users.email de "global unica" para
 * COMPOSITE UNIQUE(email, company_id).
 *
 * REGRA DE NEGOCIO (confirmada pelo usuario em 2026-06-11):
 *   Mesma pessoa pode ter contas em duas empresas com mesmo email.
 *   A unicidade e composta: a chave que identifica um usuario e o
 *   par (email, company_id), nao mais so o email.
 *
 * IMPLICACOES:
 *
 * 1. Login agora pode ter ambiguidade quando o email tem 2+ matches.
 *    O auth provider precisa de logica adicional para escolher qual
 *    usuario autenticar (ex: solicitar empresa apos email/senha).
 *    Ver tarefa separada de "Login multi-tenant".
 *
 * 2. MariaDB/MySQL trata NULL como NAO IGUAL para UNIQUE constraints.
 *    Portanto multiplos usuarios com email igual e company_id NULL
 *    (estado entre signup e onboarding) NAO violam a constraint. Eles
 *    so violarao quando company_id for setado no onboarding.
 *
 * 3. Master admin tem company_id NULL e email unico - sem conflito.
 *
 * Migration intencionalmente NAO migra dados existentes - assume que
 * o estado atual ja respeita a nova constraint (1 user por email no
 * sistema). Em rollback, restaura UNIQUE global apenas se nao houver
 * duplicacao real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop o index unique antigo. O nome padrao Laravel e
            // {tabela}_{coluna}_unique = users_email_unique
            try {
                $table->dropUnique(['email']);
            } catch (\Throwable $e) {
                // Ja foi dropado em migration anterior - ok
            }
        });

        // Cria o composite unique. Nome explicito para facilitar
        // identificacao em information_schema.
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email', 'company_id'], 'users_email_company_unique');
        });
    }

    public function down(): void
    {
        // Pre-condicao para reverter: nao pode haver dois users com
        // mesmo email (mesmo que em companies diferentes), senao a
        // restauracao da UNIQUE global vai falhar.
        $duplicates = DB::table('users')
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $emails = $duplicates->pluck('email')->implode(', ');
            throw new \RuntimeException(
                "Nao e possivel reverter: existem usuarios com email duplicado em empresas diferentes ({$emails}). Resolva manualmente antes de rollback."
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_company_unique');
            $table->unique('email');
        });
    }
};
