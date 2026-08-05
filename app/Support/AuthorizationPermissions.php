<?php

namespace App\Support;

final class AuthorizationPermissions
{
    public const ACTIONS = ['view', 'list', 'create', 'edit', 'delete', 'manage'];

    public const RESOURCES = [
        'dashboard',
        'auditorias',
        'naoconformidades',
        'planosacao',
        'politica-qualidade',
        'escopo',
        'objetivos-qualidade',
        'nossa-historia',
        'missao-visao-valores',
        'controle-documentos',
        'atas-reuniao',
        'controle-calibracoes',
        'fornecedores',
        'mapas-risco',
        'analise-swot',
        'companies',
        'users',
        'modules',
        'projetos',
        'tarefas',
        'kanban-colunas',
        'funcionarios',
        'ferias',
        'areas',
        'cargos',
        'beneficios',
        'folha-pagamento',
        'treinamentos-cursos',
        'treinamentos-locais',
        'treinamentos-turmas',
        'treinamentos-metas',
    ];

    public const BUSINESS_ROLES = [
        'Administrador',
        'Analista da Qualidade',
        'Tecnico da Qualidade',
        'Enfermeiros',
        'Tecnicos de Enfermagem',
    ];

    public static function all(): array
    {
        $permissions = ['iso-9001'];

        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = $action.'-'.$resource;
            }
        }

        return $permissions;
    }
}
