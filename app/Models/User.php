<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * IMPORTANTE — implements MustVerifyEmail:
 *   O middleware `verified` no Laravel só BLOQUEIA acesso de usuários
 *   não-verificados se o model User implementar esta interface. Sem
 *   isso, a rota Route::middleware(['auth','verified']) passa direto
 *   para qualquer usuário autenticado, seja verificado ou não.
 *
 *   Detectado pelos testes adversários cross-tenant na Fase 3.B.
 *   Antes: 'verified' middleware era cosmético.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * NOTA DE SEGURANÇA (auditoria multiempresa 2026-05-31):
     * Os campos abaixo foram REMOVIDOS do $fillable para bloquear escalada
     * de privilégio via mass-assignment (ex.: POST /register com
     * is_master_admin=1 no body):
     *   - is_master_admin       → só pode ser setado por seeder/comando ou
     *                              atribuição explícita ($user->is_master_admin = ...)
     *   - company_id            → tenant é atribuído pela trait Tenantable
     *                              em entidades-filho, não diretamente no User
     *   - legacy_adms_user_id   → campo de migração legada, sem uso em UI
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_master_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relacionamento N:N com Company via pivot company_user.
     * Padrão Eloquent: relacionamento simples e previsivel.
     *
     * NOTA — Master admin e pivot:
     *   Master admin (is_master_admin = true) NAO esta no pivot por
     *   design. Ele tem acesso global via TenantScope bypass +
     *   AbstractTenantPolicy::before() — nao precisa de linhas em
     *   company_user. Codigo que itera sobre $user->companies para
     *   exibicao UI deve verificar antes:
     *
     *     if ($user->is_master_admin) {
     *         $accessibleCompanies = Company::all();
     *     } else {
     *         $accessibleCompanies = $user->companies;
     *     }
     *
     *   Ou usar o helper accessibleCompanies() abaixo.
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    /**
     * Coleção das empresas acessíveis pelo usuário (para uso em UI).
     *
     * - Master admin: retorna TODAS as companies (acesso global)
     * - Demais: retorna as empresas vinculadas via pivot company_user
     *
     * Retorna sempre Collection (não Relation) - usar para listagens
     * de exibição, não para queries Eloquent complexas.
     */
    public function accessibleCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->is_master_admin) {
            return Company::orderBy('nome_fantasia')->get();
        }
        return $this->companies()->orderBy('nome_fantasia')->get();
    }
}
