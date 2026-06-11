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
     *
     * COMPORTAMENTO ESPECIAL para master admin:
     *   Master admin tem acesso GLOBAL a todas as empresas (regra 1
     *   da memoria sgi-laravel-access-rules). Para evitar bloat de
     *   pivot (uma linha por empresa por master admin), sobrescrevemos
     *   a query da relacao para retornar Company::all() quando
     *   is_master_admin = true.
     *
     *   Isso mantem o codigo cliente simples: $user->companies sempre
     *   retorna a lista de empresas acessiveis, independente do papel.
     *
     *   UI (Admin/Users/Index, Admin/Users/Form) e outros lugares que
     *   iteram sobre user.companies veem master admin como se ele
     *   "pertencesse" a todas as empresas, refletindo a realidade do
     *   acesso global.
     */
    public function companies()
    {
        if ($this->is_master_admin) {
            // Master admin: faz a relacao apontar para companies
            // diretamente, sem JOIN no pivot. Equivalente a
            // Company::query() mas mantem o tipo Relation.
            $relation = $this->belongsToMany(Company::class);
            // Substitui a query subjacente por SELECT * FROM companies
            // sem filtro algum.
            $query = $relation->getQuery();
            $query->getQuery()->wheres = [];
            $query->getQuery()->joins = [];
            $query->getQuery()->bindings = [
                'select' => [], 'from' => [], 'join' => [], 'where' => [],
                'groupBy' => [], 'having' => [], 'order' => [], 'union' => [],
                'unionOrder' => [],
            ];
            return $relation;
        }

        return $this->belongsToMany(Company::class);
    }
}
