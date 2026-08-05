<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcessoSeletivo;
use App\Models\Candidato;
use App\Models\Company;

class ProcessoSeletivoSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            return;
        }

        $etapas = [
            'Triagem de Currículo', 
            'Teste Prático', 
            'Dinâmica de Grupo', 
            'Entrevista Inicial', 
            'Entrevista com Gerentes', 
            'Entrevista Final', 
            'Aprovado', 
            'Reprovado'
        ];

        foreach ($companies as $company) {
            for ($i = 1; $i <= 10; $i++) {
                $processo = ProcessoSeletivo::create([
                    'company_id' => $company->id,
                    'nome' => 'Processo Seletivo ' . fake()->jobTitle(),
                    'status' => fake()->randomElement(['Em Andamento', 'Concluído', 'Cancelado']),
                    'data_inicio' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                    'data_fim' => fake()->optional(0.5)->dateTimeBetween('now', '+2 months')?->format('Y-m-d'),
                    'custo_planejado' => fake()->randomFloat(2, 1000, 10000),
                    'custo_realizado' => fake()->randomFloat(2, 800, 12000),
                ]);

                // Create 5 to 15 candidates per process
                $numCandidatos = rand(5, 15);
                for ($j = 0; $j < $numCandidatos; $j++) {
                    Candidato::create([
                        'company_id' => $company->id,
                        'processo_seletivo_id' => $processo->id,
                        'nome' => fake()->name(),
                        'email' => fake()->safeEmail(),
                        'telefone' => fake()->phoneNumber(),
                        'idade' => fake()->numberBetween(18, 60),
                        'endereco' => fake()->streetAddress(),
                        'bairro' => fake()->citySuffix(),
                        'cidade_estado' => fake()->city() . ' - ' . fake()->stateAbbr(),
                        'nivel_ensino' => fake()->randomElement(['Ensino Médio', 'Ensino Superior Incompleto', 'Ensino Superior Completo', 'Pós-graduação']),
                        'faculdade' => fake()->company(),
                        'experiencia_anos' => fake()->numberBetween(0, 15),
                        'ultima_empresa' => fake()->company(),
                        'cargo' => fake()->jobTitle(),
                        'tempo_ultimo_emprego' => fake()->numberBetween(0, 10),
                        'avaliacao_geral' => fake()->optional()->realText(),
                        'referencias' => fake()->optional()->realText(),
                        'etapa_atual' => fake()->randomElement($etapas),
                    ]);
                }
            }
        }
    }
}
