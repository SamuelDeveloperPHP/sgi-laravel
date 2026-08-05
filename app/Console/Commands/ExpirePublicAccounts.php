<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ModuleAccess;
use Illuminate\Console\Command;

class ExpirePublicAccounts extends Command
{
    protected $signature = 'sgi:expire-public-accounts';

    protected $description = 'Bloqueia contas publicas temporarias vencidas e envia aviso por e-mail';

    public function handle(): int
    {
        $users = User::query()
            ->where('is_public_account', true)
            ->where('is_active', true)
            ->whereNotNull('public_access_expires_at')
            ->where('public_access_expires_at', '<=', now())
            ->get();

        foreach ($users as $user) {
            ModuleAccess::expirePublicAccountIfNeeded($user, true);
        }

        $this->info($users->count().' conta(s) publica(s) bloqueada(s).');

        return self::SUCCESS;
    }
}
