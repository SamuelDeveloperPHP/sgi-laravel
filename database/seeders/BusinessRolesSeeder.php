<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder das 5 roles de negocio do sistema SGI (memoria
 * sgi-laravel-access-rules item 5).
 *
 * As roles definem o GRUPO funcional dentro de uma empresa:
 *   - Administrador          : acesso total aos modulos da empresa
 *                              (e quem cadastrou a empresa no
 *                              onboarding por default)
 *   - Analista da Qualidade  : auditorias, NCs, planos, indicadores
 *   - Tecnico da Qualidade   : operacional dos processos da qualidade
 *   - Enfermeiros            : modulos clinicos/saude
 *   - Tecnicos de Enfermagem : operacional clinico
 *
 * Master Admin NAO esta nesta lista - e um papel transversal de
 * sistema (users.is_master_admin = true), nao atribuido via UI.
 *
 * Idempotente: usa firstOrCreate. Pode rodar repetidamente sem
 * duplicar.
 *
 * NOTE: as permissions atribuidas aqui sao um ponto de partida
 * razoavel. O Administrador da empresa pode ajustar finamente as
 * permissions de cada role pelo /admin/users (matriz granular).
 */
class BusinessRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa cache antes de mexer
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Administrador da empresa
        //    Tudo dentro da empresa, exceto modulos restritos a
        //    master admin (Projetos e Modulos). Nao precisa de
        //    permission especial para Projetos/Modules porque eles
        //    sao bloqueados via middleware CheckMasterAdmin nas rotas.
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $adminPermissions = $this->safePermissionsByPrefix([
            'view-', 'list-', 'create-', 'edit-', 'delete-', 'manage-',
        ]);
        // Remove permissions de modulos restritos a master admin
        $adminPermissions = $adminPermissions->reject(function ($name) {
            return str_contains($name, 'projetos')
                || str_contains($name, 'modules')
                || str_contains($name, 'companies'); // master admin gerencia companies
        });
        $admin->syncPermissions($adminPermissions->all());

        // 2. Analista da Qualidade
        //    Foco em auditorias, nao conformidades, planos de acao,
        //    objetivos e politica da qualidade.
        $analista = Role::firstOrCreate(['name' => 'Analista da Qualidade', 'guard_name' => 'web']);
        $analista->syncPermissions($this->permissionsForPrefixes([
            'view-dashboard',
            'view-auditorias', 'create-auditorias', 'edit-auditorias',
            'view-naoconformidades', 'create-naoconformidades', 'edit-naoconformidades',
            'view-planosacao', 'create-planosacao', 'edit-planosacao',
            'view-objetivos-qualidade', 'create-objetivos-qualidade', 'edit-objetivos-qualidade',
            'view-politica-qualidade', 'manage-politica-qualidade',
            'view-escopo', 'manage-escopo',
            'view-nossa-historia', 'manage-nossa-historia',
            'view-missao-visao-valores', 'manage-missao-visao-valores',
            'view-controle-documentos', 'manage-controle-documentos',
            'view-atas-reuniao', 'manage-atas-reuniao',
            'view-controle-calibracoes', 'manage-controle-calibracoes',
            'view-fornecedores', 'manage-fornecedores',
        ]));

        // 3. Tecnico da Qualidade
        //    Operacional - registra ocorrencias, executa acoes,
        //    sem editar documentos formais.
        $tecnicoQ = Role::firstOrCreate(['name' => 'Tecnico da Qualidade', 'guard_name' => 'web']);
        $tecnicoQ->syncPermissions($this->permissionsForPrefixes([
            'view-dashboard',
            'view-auditorias',
            'view-naoconformidades', 'create-naoconformidades',
            'view-planosacao',
            'view-objetivos-qualidade',
            'view-controle-documentos',
            'view-controle-calibracoes',
            'view-fornecedores',
        ]));

        // 4. Enfermeiros
        //    Acesso aos modulos clinicos/saude (futuros). Por enquanto,
        //    visualizacao basica + NC clinica.
        $enfermeiro = Role::firstOrCreate(['name' => 'Enfermeiros', 'guard_name' => 'web']);
        $enfermeiro->syncPermissions($this->permissionsForPrefixes([
            'view-dashboard',
            'view-naoconformidades', 'create-naoconformidades', 'edit-naoconformidades',
            'view-planosacao',
            'view-controle-documentos',
            'view-atas-reuniao',
        ]));

        // 5. Tecnicos de Enfermagem
        //    Operacional clinico - registra NC, sem editar.
        $tecnicoE = Role::firstOrCreate(['name' => 'Tecnicos de Enfermagem', 'guard_name' => 'web']);
        $tecnicoE->syncPermissions($this->permissionsForPrefixes([
            'view-dashboard',
            'view-naoconformidades', 'create-naoconformidades',
            'view-controle-documentos',
        ]));

        $this->command->info('5 roles de negocio criadas/atualizadas.');
    }

    /**
     * Retorna uma colecao de nomes de permissions que existem no
     * banco filtradas pelos prefixos informados.
     */
    private function safePermissionsByPrefix(array $prefixes)
    {
        return Permission::all()
            ->pluck('name')
            ->filter(function ($name) use ($prefixes) {
                foreach ($prefixes as $p) {
                    if (str_starts_with($name, $p)) {
                        return true;
                    }
                }
                return false;
            })
            ->values();
    }

    /**
     * Retorna apenas as permissions que existem no banco a partir
     * de uma lista de nomes. Permissions nao-existentes sao
     * silenciosamente ignoradas (modulos futuros).
     */
    private function permissionsForPrefixes(array $names): array
    {
        $existing = Permission::whereIn('name', $names)->pluck('name')->all();
        return $existing;
    }
}
