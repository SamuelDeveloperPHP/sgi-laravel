<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Curso;
use App\Models\LocalTreinamento;
use App\Models\Treinamento;
use App\Models\TreinamentoPresenca;
use App\Models\Funcionario;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TreinamentosSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        if (!$company) {
            echo "Nenhuma company encontrada. Abortando.\n";
            return;
        }

        $faker = Faker::create('pt_BR');

        // Locais
        $locais = [];
        for ($i = 1; $i <= 10; $i++) {
            $locais[] = LocalTreinamento::create([
                'company_id' => $company->id,
                'nome' => 'Sala ' . ucfirst($faker->word) . ' - Bloco ' . rand(1, 5)
            ]);
        }

        // Cursos
        $cursos = [];
        $nomesCursos = [
            'NR-10 Segurança em Instalações e Serviços com Eletricidade',
            'NR-35 Trabalho em Altura',
            'Brigada de Incêndio',
            'Primeiros Socorros',
            'Gestão de Qualidade ISO 9001',
            'Liderança e Gestão de Equipes',
            'Comunicação Não Violenta',
            'CIPA - Comissão Interna de Prevenção',
            'Ergonomia no Ambiente de Trabalho',
            'Segurança da Informação e LGPD'
        ];
        
        foreach ($nomesCursos as $nome) {
            $cursos[] = Curso::create([
                'company_id' => $company->id,
                'nome' => $nome,
                'descricao' => $faker->realText(100),
                'carga_horaria' => $faker->randomElement([4, 8, 12, 16, 20, 40]),
            ]);
        }

        // Treinamentos
        $treinamentos = [];
        $statusEnum = ['Agendado', 'Em Andamento', 'Concluído', 'Cancelado'];
        for ($i = 1; $i <= 10; $i++) {
            $data_inicio = Carbon::now()->addDays(rand(-30, 30));
            $treinamentos[] = Treinamento::create([
                'company_id' => $company->id,
                'curso_id' => $faker->randomElement($cursos)->id,
                'local_treinamento_id' => $faker->randomElement($locais)->id,
                'instrutor' => $faker->name,
                'data_inicio' => $data_inicio,
                'data_fim' => $data_inicio->copy()->addDays(rand(0, 3)),
                'status' => $faker->randomElement($statusEnum),
            ]);
        }

        // Presencas
        $funcionarios = Funcionario::where('company_id', $company->id)->take(20)->get();
        if ($funcionarios->count() > 0) {
            foreach ($treinamentos as $treinamento) {
                $numAlunos = rand(3, min(8, $funcionarios->count()));
                $alunos = $funcionarios->random($numAlunos);
                foreach ($alunos as $aluno) {
                    TreinamentoPresenca::create([
                        'company_id' => $company->id,
                        'treinamento_id' => $treinamento->id,
                        'funcionario_id' => $aluno->id,
                        'presente' => $faker->boolean(80),
                    ]);
                }
            }
        }
        
        echo "Foram gerados 10 Locais, 10 Cursos e 10 Treinamentos com listas de presenças aleatórias.\n";
    }
}
