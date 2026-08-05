<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed seguro para ambientes reais: cria somente configuracao de
        // autorizacao e navegacao. Contas de demonstracao devem ser criadas
        // por comando/processo explicito, nunca automaticamente em producao.
        $this->call([
            ModuleSeeder::class,
            RolesAndPermissionsSeeder::class,
            BusinessRolesSeeder::class,
        ]);
    }
}
