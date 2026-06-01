<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
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

    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }
}
