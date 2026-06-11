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
                'slug' => 'list-dashboard',
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
                    ['name' => 'Nossa História', 'slug' => 'list-nossa-historia', 'route_name' => 'nossa-historia.index', 'order' => 1],
                    ['name' => 'Missão, Visão e Valores', 'slug' => 'list-missao-visao-valores', 'route_name' => 'missao-visao-valores.index', 'order' => 2],
                    ['name' => 'Política da Qualidade', 'slug' => 'list-politica-qualidade', 'route_name' => 'politica-qualidade.index', 'order' => 3],
                    ['name' => 'Escopo do SGI', 'slug' => 'list-escopo', 'route_name' => 'escopo.index', 'order' => 4],
                    ['name' => 'Objetivos da Qualidade', 'slug' => 'list-objetivos-qualidade', 'route_name' => 'objetivos-qualidade.index', 'order' => 5],
                    ['name' => 'Controle de Documentos', 'slug' => 'list-controle-documentos', 'route_name' => 'controle-documentos.index', 'order' => 6],
                    ['name' => 'Controle de Calibrações', 'slug' => 'list-controle-calibracoes', 'route_name' => 'controle-calibracoes.index', 'order' => 7],
                    ['name' => 'Gestão de Fornecedores', 'slug' => 'list-fornecedores', 'route_name' => 'fornecedores.index', 'order' => 8],
                    ['name' => 'Atas de Reuniões', 'slug' => 'list-atas-reuniao', 'route_name' => 'atas-reuniao.index', 'order' => 9],
                    ['name' => 'Auditorias', 'slug' => 'list-auditorias', 'route_name' => 'auditorias.index', 'order' => 10],
                    ['name' => 'Não Conformidades', 'slug' => 'list-naoconformidades', 'route_name' => 'nao-conformidades.index', 'order' => 11],
                    ['name' => 'Planos de Ação', 'slug' => 'list-planosacao', 'route_name' => 'planos-acao.index', 'order' => 12],
                ]
            ],
            [
                'name' => 'Projetos',
                'slug' => 'list-projetos',
                'route_name' => 'projetos.index',
                'icon' => 'Briefcase',
                'order' => 30,
            ],
            [
                'name' => 'Empresas',
                'slug' => 'list-companies',
                'route_name' => 'admin.companies.index',
                'icon' => 'Building',
                'order' => 40,
            ],
            [
                'name' => 'Usuários',
                'slug' => 'list-users',
                'route_name' => 'admin.users.index',
                'icon' => 'Users',
                'order' => 50,
            ],
            [
                'name' => 'Módulos',
                'slug' => 'list-modules',
                'route_name' => 'admin.modules.index',
                'icon' => 'Blocks', // Using Blocks from lucide-react as a puzzle piece alternative
                'order' => 60,
            ],
        ];

        $actionVerbs = ['view', 'list', 'create', 'edit', 'delete', 'manage'];

        foreach ($modules as $mod) {
            $children = $mod['children'] ?? [];
            unset($mod['children']);

            $parent = \App\Models\Module::updateOrCreate(
                ['name' => $mod['name']],
                $mod
            );

            // Create Spatie permissions for parent (if it's not just a folder like ISO 9001)
            if ($parent->slug !== 'iso-9001') {
                $resource = str_replace('list-', '', $parent->slug);
                foreach ($actionVerbs as $verb) {
                    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => "{$verb}-{$resource}"]);
                }
            } else {
                // For iso-9001 just create its slug so it doesn't break anything expecting it
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $parent->slug]);
            }

            foreach ($children as $child) {
                $child['parent_id'] = $parent->id;
                $childModel = \App\Models\Module::updateOrCreate(
                    ['name' => $child['name']],
                    $child
                );
                
                $resource = str_replace('list-', '', $childModel->slug);
                foreach ($actionVerbs as $verb) {
                    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => "{$verb}-{$resource}"]);
                }
            }
        }
    }
}
