<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de DEMONSTRAÇÃO: cria um projeto "Viagem a Los Angeles" com um
 * cronograma de tarefas (grupos, dependências e marcos) para exercitar a
 * visão Gantt em /projetos.
 *
 * Aditivo e idempotente: remove apenas a própria execução anterior (pelo nome
 * do projeto) e recria. NÃO faz parte do DatabaseSeeder automático — rode sob
 * demanda:  php artisan db:seed --class=ViagemLosAngelesSeeder
 */
class ViagemLosAngelesSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;                    // tenant usado pelos projetos existentes (FK -> companies)
        $userId = 1;                       // Administrador Master
        $now = now()->toDateTimeString();
        $nomeProjeto = 'Planejamento de Viagem a Los Angeles ✈️';

        // Idempotência MANTENDO o mesmo id (não quebra links/abas ao re-semear): atualiza
        // o projeto no lugar e recria as tarefas; só cria projeto novo se ainda não existir.
        $existente = DB::table('sts_projetos')
            ->where('company_id', $companyId)
            ->where('nomeProjeto', $nomeProjeto)
            ->first();

        $dadosProjeto = [
            'descricao'           => '<p>Cronograma completo de uma viagem de avião a Los Angeles (EUA): documentação, reservas, preparação e a viagem.</p>',
            'data_inicio'         => '2026-08-03',
            'data_fim'            => '2026-09-02',
            'ativo'               => '1',
            'porc_concluido'      => 32,
            'ordem'               => 0,
            'nivel_prioridade_id' => 1,
            'cor_prioridade_id'   => 10,
            'adms_cor_id'         => 1,
            'adms_sit_id'         => 5,
            'adms_usuario_id'     => $userId,
            'responsavel_id'      => $userId,
            'company_id'          => $companyId,
            'modified'            => $now,
        ];

        if ($existente) {
            $projetoId = $existente->id;
            $tarefaIds = DB::table('sts_tarefas_projeto')->where('projeto_id', $projetoId)->pluck('id');
            if ($tarefaIds->isNotEmpty()) {
                DB::table('tarefa_projeto_user')->whereIn('tarefa_projeto_id', $tarefaIds)->delete();
            }
            DB::table('sts_tarefas_projeto')->where('projeto_id', $projetoId)->delete();
            DB::table('sts_projetos')->where('id', $projetoId)->update($dadosProjeto);
        } else {
            $projetoId = DB::table('sts_projetos')->insertGetId(
                $dadosProjeto + ['nomeProjeto' => $nomeProjeto, 'created' => $now]
            );
        }

        // Outline: level 1 = grupo; level 2 = tarefa; ms = marco. deps por chave.
        $defs = [
            ['key' => 'A',  'level' => 1, 'nome' => 'Planejamento & Documentação',                        'ini' => '2026-08-03', 'dur' => 19, 'fim' => '2026-08-22', 'prog' => 60,  'ms' => false, 'deps' => []],
            ['key' => 'A1', 'level' => 2, 'nome' => 'Definir datas, roteiro e orçamento',                 'ini' => '2026-08-03', 'dur' => 3,  'fim' => '2026-08-05', 'prog' => 100, 'ms' => false, 'deps' => []],
            ['key' => 'A2', 'level' => 2, 'nome' => 'Renovar/emitir passaporte',                          'ini' => '2026-08-05', 'dur' => 10, 'fim' => '2026-08-15', 'prog' => 80,  'ms' => false, 'deps' => ['A1']],
            ['key' => 'A3', 'level' => 2, 'nome' => 'Solicitar visto / ESTA (EUA)',                       'ini' => '2026-08-15', 'dur' => 7,  'fim' => '2026-08-22', 'prog' => 40,  'ms' => false, 'deps' => ['A2']],

            ['key' => 'B',  'level' => 1, 'nome' => 'Reservas',                                            'ini' => '2026-08-06', 'dur' => 6,  'fim' => '2026-08-12', 'prog' => 55,  'ms' => false, 'deps' => []],
            ['key' => 'B1', 'level' => 2, 'nome' => 'Comprar passagens aéreas (GRU – LAX)',               'ini' => '2026-08-06', 'dur' => 2,  'fim' => '2026-08-08', 'prog' => 100, 'ms' => false, 'deps' => ['A1']],
            ['key' => 'B2', 'level' => 2, 'nome' => 'Reservar hotel em Los Angeles',                      'ini' => '2026-08-08', 'dur' => 2,  'fim' => '2026-08-10', 'prog' => 60,  'ms' => false, 'deps' => ['B1']],
            ['key' => 'B3', 'level' => 2, 'nome' => 'Alugar carro / transporte',                          'ini' => '2026-08-10', 'dur' => 1,  'fim' => '2026-08-11', 'prog' => 0,   'ms' => false, 'deps' => ['B2']],
            ['key' => 'B4', 'level' => 2, 'nome' => 'Contratar seguro viagem',                            'ini' => '2026-08-11', 'dur' => 1,  'fim' => '2026-08-12', 'prog' => 0,   'ms' => false, 'deps' => ['B1']],

            ['key' => 'C',  'level' => 1, 'nome' => 'Preparação',                                          'ini' => '2026-08-12', 'dur' => 12, 'fim' => '2026-08-24', 'prog' => 15,  'ms' => false, 'deps' => []],
            ['key' => 'C1', 'level' => 2, 'nome' => 'Montar roteiro (Hollywood, Santa Monica, Disneyland)', 'ini' => '2026-08-12', 'dur' => 3, 'fim' => '2026-08-15', 'prog' => 30,  'ms' => false, 'deps' => ['B2']],
            ['key' => 'C2', 'level' => 2, 'nome' => 'Câmbio e cartão internacional',                      'ini' => '2026-08-15', 'dur' => 2,  'fim' => '2026-08-17', 'prog' => 0,   'ms' => false, 'deps' => ['A1']],
            ['key' => 'C3', 'level' => 2, 'nome' => 'Reservar passeios / ingressos',                      'ini' => '2026-08-17', 'dur' => 2,  'fim' => '2026-08-19', 'prog' => 0,   'ms' => false, 'deps' => ['C1']],
            ['key' => 'C4', 'level' => 2, 'nome' => 'Check-in online e assentos',                         'ini' => '2026-08-22', 'dur' => 1,  'fim' => '2026-08-23', 'prog' => 0,   'ms' => false, 'deps' => ['B1']],
            ['key' => 'C5', 'level' => 2, 'nome' => 'Fazer as malas',                                     'ini' => '2026-08-23', 'dur' => 1,  'fim' => '2026-08-24', 'prog' => 0,   'ms' => false, 'deps' => ['C1']],

            ['key' => 'M1', 'level' => 1, 'nome' => '✈️ Embarque — voo para Los Angeles',                 'ini' => '2026-08-25', 'dur' => 0,  'fim' => '2026-08-25', 'prog' => 0,   'ms' => true,  'deps' => ['A3', 'C4', 'C5']],

            ['key' => 'D',  'level' => 1, 'nome' => 'Viagem',                                              'ini' => '2026-08-25', 'dur' => 8,  'fim' => '2026-09-02', 'prog' => 0,   'ms' => false, 'deps' => []],
            ['key' => 'D1', 'level' => 2, 'nome' => 'Voo GRU → LAX',                                       'ini' => '2026-08-25', 'dur' => 1,  'fim' => '2026-08-26', 'prog' => 0,   'ms' => false, 'deps' => ['M1']],
            ['key' => 'D2', 'level' => 2, 'nome' => 'Estadia em Los Angeles',                             'ini' => '2026-08-26', 'dur' => 6,  'fim' => '2026-09-01', 'prog' => 0,   'ms' => false, 'deps' => ['D1']],
            ['key' => 'D3', 'level' => 2, 'nome' => 'Voo de retorno LAX → GRU',                           'ini' => '2026-09-01', 'dur' => 1,  'fim' => '2026-09-02', 'prog' => 0,   'ms' => false, 'deps' => ['D2']],

            ['key' => 'M2', 'level' => 1, 'nome' => 'Retorno ao Brasil (marco final)',                     'ini' => '2026-09-02', 'dur' => 0,  'fim' => '2026-09-02', 'prog' => 0,   'ms' => true,  'deps' => ['D3']],
        ];

        // Responsável de cada tarefa (id de usuário). Só há 2 usuários no ambiente:
        // 1 = Administrador Master, 2 = Analista da Qualidade.
        $resps = [
            'A' => 1, 'A1' => 1, 'A2' => 2, 'A3' => 2,
            'B' => 1, 'B1' => 1, 'B2' => 2, 'B3' => 1, 'B4' => 2,
            'C' => 1, 'C1' => 2, 'C2' => 1, 'C3' => 2, 'C4' => 1, 'C5' => 2,
            'M1' => 1,
            'D' => 1, 'D1' => 2, 'D2' => 1, 'D3' => 2,
            'M2' => 1,
        ];

        $idMap = [];
        $ordem = 1;
        foreach ($defs as $d) {
            $isGroup = $d['level'] === 1 && ! $d['ms'];
            $idMap[$d['key']] = DB::table('sts_tarefas_projeto')->insertGetId([
                'nome'              => $d['nome'],
                'descricao'         => '',
                'ordem'             => $ordem++,
                'progresso'         => $d['prog'],
                'type'              => 0,
                'typeId'            => 0,
                'assigs'            => 0,
                'level'             => $d['level'],
                'status'            => 'STATUS_ACTIVE',
                'code'              => $d['key'],
                'dependencias'      => '',      // preenchido na 2ª passada
                'dt_inicio'         => $d['ini'],
                'tempo_duracao'     => $d['dur'],
                'dt_fim'            => $d['fim'],
                'startIsMilestone'  => $d['ms'] ? 'true' : 'false',
                'endIsMilestone'    => $d['ms'] ? 'true' : 'false',
                'collapsed'         => 'false',
                'hasChild'          => $isGroup ? 'true' : 'false',
                'adms_cor_id'       => 1,
                'adms_sit_id'       => 1,
                'adms_usuario_id'   => $userId,
                'user_update_id'    => $userId,
                'cor_prioridade_id' => 7,
                'projeto_id'        => $projetoId,
                'company_id'        => $companyId,
                'created'           => $now,
                'modified'          => $now,
            ]);

            // Responsável pela execução (pivot tarefa_projeto_user).
            DB::table('tarefa_projeto_user')->insert([
                'tarefa_projeto_id' => $idMap[$d['key']],
                'user_id'           => $resps[$d['key']] ?? $userId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }

        // 2ª passada: converte chaves de dependência em IDs reais.
        foreach ($defs as $d) {
            if (empty($d['deps'])) {
                continue;
            }
            $ids = array_map(fn ($k) => $idMap[$k], $d['deps']);
            DB::table('sts_tarefas_projeto')
                ->where('id', $idMap[$d['key']])
                ->update(['dependencias' => implode(',', $ids)]);
        }

        $this->command->info("Projeto '{$nomeProjeto}' criado (id {$projetoId}) com " . count($defs) . ' tarefas.');
    }
}
