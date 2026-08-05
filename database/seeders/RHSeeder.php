<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Funcionario;
use App\Models\Ferias;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RHSeeder extends Seeder
{
    public function run(): void
    {
        $masterCompany = Company::first();

        if (!$masterCompany) {
            $this->command->error('Nenhuma empresa cadastrada para vincular os funcionários.');
            return;
        }

        // Cria 3 Áreas
        $areas = [];
        $nomesAreas = ['Diretoria', 'Recursos Humanos', 'Tecnologia da Informação', 'Vendas', 'Operações'];
        foreach ($nomesAreas as $nome) {
            $areas[] = \App\Models\Area::create([
                'company_id' => $masterCompany->id,
                'nome' => $nome,
                'descricao' => 'Departamento de ' . $nome,
            ]);
        }

        // Cria 5 Cargos
        $cargos = [];
        $nomesCargos = [
            ['nome' => 'Diretor Geral', 'salario' => 15000],
            ['nome' => 'Gerente de TI', 'salario' => 8000],
            ['nome' => 'Analista de RH', 'salario' => 3500],
            ['nome' => 'Vendedor', 'salario' => 2000],
            ['nome' => 'Auxiliar Administrativo', 'salario' => 1621]
        ];
        foreach ($nomesCargos as $cargoData) {
            $cargos[] = \App\Models\Cargo::create([
                'company_id' => $masterCompany->id,
                'nome' => $cargoData['nome'],
                'salario_base' => $cargoData['salario'],
            ]);
        }

        $faker = Faker::create('pt_BR');

        DB::beginTransaction();
        try {
            $funcionarios = [];
            for ($i = 0; $i < 10; $i++) {
                $status = $faker->randomElement(['Ativo', 'Ativo', 'Ativo', 'Ativo', 'Férias', 'Afastado', 'Desligado']);
                $admissao = $faker->dateTimeBetween('-5 years', '-1 year');
                
                $area = $faker->randomElement($areas);
                $cargo = $faker->randomElement($cargos);

                $funcionario = Funcionario::create([
                    'company_id' => $masterCompany->id,
                    'nome' => $faker->name,
                    'cpf' => $faker->unique()->cpf(false),
                    'matricula' => $faker->unique()->numerify('MAT-####'),
                    'data_admissao' => $admissao->format('Y-m-d'),
                    'dependentes' => $faker->numberBetween(0, 3),
                    'estado_civil' => $faker->randomElement(['Solteiro', 'Casado', 'Divorciado']),
                    'salario_bruto' => $cargo->salario_base,
                    'telefone' => $faker->phoneNumber,
                    'email' => $faker->unique()->safeEmail,
                    'status' => $status == 'Desligado' ? 'Inativo' : $status,
                    
                    // Novos Campos
                    'area_id' => $area->id,
                    'cargo_id' => $cargo->id,
                    'genero' => $faker->randomElement(['M', 'F', 'O']),
                    'data_demissao' => $status == 'Desligado' ? $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d') : null,
                    'motivo_demissao' => $status == 'Desligado' ? 'Sem Justa Causa' : null,
                    
                    // Dados de Endereço
                    'cep' => $faker->numerify('########'),
                    'logradouro' => $faker->streetName,
                    'numero' => $faker->buildingNumber,
                    'complemento' => $faker->optional()->secondaryAddress,
                    'bairro' => $faker->citySuffix,
                    'cidade' => $faker->city,
                    'estado' => $faker->stateAbbr,
                    'created_by' => 1,
                ]);

                $funcionarios[] = $funcionario;

                // Create Folha Pagamento History (Last 6 months)
                for ($m = 6; $m >= 1; $m--) {
                    $competencia = Carbon::now()->subMonths($m)->format('Y-m');
                    // Skip if demitido before this comp
                    if ($funcionario->data_demissao && Carbon::parse($funcionario->data_demissao)->format('Y-m') < $competencia) {
                        continue;
                    }
                    // Skip if admitted after this comp
                    if (Carbon::parse($funcionario->data_admissao)->format('Y-m') > $competencia) {
                        continue;
                    }

                    \App\Models\FolhaPagamento::create([
                        'company_id' => $masterCompany->id,
                        'funcionario_id' => $funcionario->id,
                        'competencia' => $competencia,
                        'salario_base' => $funcionario->salario_bruto,
                        'total_proventos' => $funcionario->salario_bruto,
                        'total_descontos' => $funcionario->salario_bruto * 0.10, // Falso desconto 10%
                        'total_beneficios' => 500.00, // Falso beneficio fixo
                        'salario_liquido' => ($funcionario->salario_bruto * 0.90),
                        'custo_total' => $funcionario->salario_bruto + 500,
                        'status' => 'Fechado'
                    ]);
                }

                // Duas programações de férias (Gozo fracionado: 15 e 15 dias) para o primeiro período aquisitivo
                $inicioAquisitivo1 = Carbon::parse($funcionario->data_admissao);
                $fimAquisitivo1 = $inicioAquisitivo1->copy()->addYear()->subDay();
                
                $gozo1Inicio = $fimAquisitivo1->copy()->addMonths(rand(1, 5));
                $gozo1Fim = $gozo1Inicio->copy()->addDays(14); // 15 dias de duração
                
                $gozo2Inicio = $gozo1Fim->copy()->addMonths(rand(2, 4));
                $gozo2Fim = $gozo2Inicio->copy()->addDays(14); // 15 dias de duração

                Ferias::create([
                    'company_id' => $masterCompany->id,
                    'funcionario_id' => $funcionario->id,
                    'periodo_aquisitivo_inicio' => $inicioAquisitivo1->toDateString(),
                    'periodo_aquisitivo_fim' => $fimAquisitivo1->toDateString(),
                    'dias_direito' => 30,
                    'opcao_abono' => false,
                    'dias_abono' => 0,
                    
                    'gozo_1_inicio' => $gozo1Inicio->toDateString(),
                    'gozo_1_fim' => $gozo1Fim->toDateString(),
                    
                    'gozo_2_inicio' => $gozo2Inicio->toDateString(),
                    'gozo_2_fim' => $gozo2Fim->toDateString(),

                    'status' => 'Programada',
                    'created_by' => 1,
                ]);

                // Segundo período aquisitivo
                $inicioAquisitivo2 = $inicioAquisitivo1->copy()->addYear();
                $fimAquisitivo2 = $inicioAquisitivo2->copy()->addYear()->subDay();
                
                $gozo3Inicio = $fimAquisitivo2->copy()->addMonths(rand(1, 5));
                $gozo3Fim = $gozo3Inicio->copy()->addDays(14); // 15 dias
                
                $gozo4Inicio = $gozo3Fim->copy()->addMonths(rand(2, 4));
                $gozo4Fim = $gozo4Inicio->copy()->addDays(14); // 15 dias

                Ferias::create([
                    'company_id' => $masterCompany->id,
                    'funcionario_id' => $funcionario->id,
                    'periodo_aquisitivo_inicio' => $inicioAquisitivo2->toDateString(),
                    'periodo_aquisitivo_fim' => $fimAquisitivo2->toDateString(),
                    'dias_direito' => 30,
                    'opcao_abono' => false,
                    'dias_abono' => 0,
                    
                    'gozo_1_inicio' => $gozo3Inicio->toDateString(),
                    'gozo_1_fim' => $gozo3Fim->toDateString(),
                    
                    'gozo_2_inicio' => $gozo4Inicio->toDateString(),
                    'gozo_2_fim' => $gozo4Fim->toDateString(),

                    'status' => 'Programada',
                    'created_by' => 1,
                ]);
            }
            DB::commit();
            $this->command->info('Seed concluído: 10 funcionários com férias de 15+15 dias cadastradas com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erro ao executar seeder: ' . $e->getMessage());
        }
    }
}
