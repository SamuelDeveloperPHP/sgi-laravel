<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Dashboard',
                'slug' => 'view-dashboard',
                'route_name' => 'dashboard',
                'icon' => 'LayoutDashboard',
                'order' => 10,
            ],
            [
                'name' => 'ISO 9001',
                'slug' => 'iso-9001',
                'route_name' => null,
                'icon' => 'Target',
                'order' => 20,
                'children' => [
                    ['name' => 'Nossa História', 'slug' => 'view-nossa-historia', 'route_name' => 'nossa-historia.index', 'order' => 1],
                    ['name' => 'Missão, Visão e Valores', 'slug' => 'view-missao-visao-valores', 'route_name' => 'missao-visao-valores.index', 'order' => 2],
                    ['name' => 'Política da Qualidade', 'slug' => 'view-politica-qualidade', 'route_name' => 'politica-qualidade.index', 'order' => 3],
                    ['name' => 'Escopo do SGI', 'slug' => 'view-escopo', 'route_name' => 'escopo.index', 'order' => 4],
                    ['name' => 'Objetivos da Qualidade', 'slug' => 'view-objetivos-qualidade', 'route_name' => 'objetivos-qualidade.index', 'order' => 5],
                    ['name' => 'Controle de Documentos', 'slug' => 'view-controle-documentos', 'route_name' => 'controle-documentos.index', 'order' => 6],
                    ['name' => 'Controle de Calibrações', 'slug' => 'view-controle-calibracoes', 'route_name' => 'controle-calibracoes.index', 'order' => 7],
                    ['name' => 'Gestão de Fornecedores', 'slug' => 'view-fornecedores', 'route_name' => 'fornecedores.index', 'order' => 8],
                    ['name' => 'Atas de Reuniões', 'slug' => 'view-atas-reuniao', 'route_name' => 'atas-reuniao.index', 'order' => 9],
                    ['name' => 'Auditorias', 'slug' => 'view-auditorias', 'route_name' => 'auditorias.index', 'order' => 10],
                    ['name' => 'Não Conformidades', 'slug' => 'view-naoconformidades', 'route_name' => 'nao-conformidades.index', 'order' => 11],
                    ['name' => 'Planos de Ação', 'slug' => 'view-planosacao', 'route_name' => 'planos-acao.index', 'order' => 12],
                ]
            ],
            [
                'name' => 'Projetos',
                'slug' => 'view-projetos',
                'route_name' => 'projetos.index',
                'icon' => 'Briefcase',
                'order' => 30,
            ],
            [
                'name' => 'Empresas',
                'slug' => 'view-companies',
                'route_name' => 'admin.companies.index',
                'icon' => 'Building',
                'order' => 40,
            ],
            [
                'name' => 'Usuários',
                'slug' => 'view-users',
                'route_name' => 'admin.users.index',
                'icon' => 'Users',
                'order' => 50,
            ],
        ];

        foreach ($modules as $mod) {
            $children = $mod['children'] ?? [];
            unset($mod['children']);

            $parent = \App\Models\Module::updateOrCreate(
                ['slug' => $mod['slug']],
                $mod
            );

            // Create Spatie permission
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $parent->slug]);

            foreach ($children as $child) {
                $child['parent_id'] = $parent->id;
                $childModel = \App\Models\Module::updateOrCreate(
                    ['slug' => $child['slug']],
                    $child
                );
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $childModel->slug]);
            }
        }
    }
}
