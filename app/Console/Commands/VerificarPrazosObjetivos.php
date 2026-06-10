<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ObjetivoQualidade;
use App\Notifications\ObjetivoPrazoNotification;
use Carbon\Carbon;

class VerificarPrazosObjetivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sgi:verificar-prazos-objetivos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica os prazos dos objetivos da qualidade e alerta os responsáveis.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $objetivos = ObjetivoQualidade::with('responsaveis')->get();

        foreach ($objetivos as $objetivo) {
            if (!$objetivo->prazo) continue;

            $hoje = Carbon::now()->startOfDay();
            $dataCriacao = $objetivo->created_at->startOfDay();
            $prazo = Carbon::parse($objetivo->prazo)->startOfDay();

            $diasTotais = $dataCriacao->diffInDays($prazo, false);
            if ($diasTotais <= 0) $diasTotais = 1; // Prevenir divisão por zero ou prazos retroativos no mesmo dia

            $diasRestantes = $hoje->diffInDays($prazo, false);
            
            $tipoAlerta = null;
            $mensagem = '';

            // Lógica: 50% para > 30 dias, 20% para <= 30 dias
            if ($diasTotais > 30) {
                $limite = $diasTotais * 0.5;
                if ($diasRestantes > 0 && $diasRestantes <= $limite) {
                    $tipoAlerta = '50_porcento';
                    $mensagem = "O prazo do objetivo '{$objetivo->titulo}' atingiu os 50% restantes (faltam {$diasRestantes} dias).";
                }
            } else {
                $limite = $diasTotais * 0.2;
                if ($diasRestantes > 0 && $diasRestantes <= $limite) {
                    $tipoAlerta = '20_porcento';
                    $mensagem = "Atenção: Falta apenas 20% do prazo para o objetivo '{$objetivo->titulo}' (faltam {$diasRestantes} dias).";
                }
            }

            // Condição de atrasado/vencido
            if ($diasRestantes < 0) {
                $tipoAlerta = 'vencido';
                $diasAtraso = abs($diasRestantes);
                $mensagem = "URGENTE: O prazo do objetivo '{$objetivo->titulo}' venceu há {$diasAtraso} dia(s).";
            } elseif ($diasRestantes === 0) {
                $tipoAlerta = 'vencido';
                $mensagem = "O prazo do objetivo '{$objetivo->titulo}' vence HOJE.";
            }

            if ($tipoAlerta) {
                foreach ($objetivo->responsaveis as $user) {
                    // Verificar se o usuário já recebeu este tipo exato de alerta para este objetivo
                    $jaNotificado = $user->notifications()
                        ->where('type', ObjetivoPrazoNotification::class)
                        ->where('data->objetivo_id', $objetivo->id)
                        ->where('data->tipo_alerta', $tipoAlerta)
                        ->exists();

                    if (!$jaNotificado) {
                        $user->notify(new ObjetivoPrazoNotification($objetivo, $mensagem, $tipoAlerta));
                        $this->info("Notificação enviada para {$user->name} sobre o objetivo {$objetivo->id} ({$tipoAlerta}).");
                    }
                }
            }
        }

        $this->info('Verificação de prazos concluída.');
    }
}
