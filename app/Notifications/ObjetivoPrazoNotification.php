<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ObjetivoQualidade;

class ObjetivoPrazoNotification extends Notification
{
    use Queueable;

    public $objetivo;
    public $mensagem;
    public $tipoAlerta;

    /**
     * Create a new notification instance.
     */
    public function __construct(ObjetivoQualidade $objetivo, $mensagem, $tipoAlerta)
    {
        $this->objetivo = $objetivo;
        $this->mensagem = $mensagem;
        $this->tipoAlerta = $tipoAlerta; // '50_porcento', '20_porcento', 'vencido'
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // We will only use internal system notifications
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'objetivo_id' => $this->objetivo->id,
            'titulo' => 'Alerta de Prazo: ' . $this->objetivo->titulo,
            'mensagem' => $this->mensagem,
            'tipo_alerta' => $this->tipoAlerta,
            'url' => route('objetivos-qualidade.show', $this->objetivo->id)
        ];
    }
}
