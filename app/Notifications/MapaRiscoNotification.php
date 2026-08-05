<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\MapaRisco;

class MapaRiscoNotification extends Notification
{
    use Queueable;

    protected MapaRisco $mapaRisco;
    protected string $titulo;
    protected string $mensagem;

    /**
     * Create a new notification instance.
     */
    public function __construct(MapaRisco $mapaRisco, string $titulo, string $mensagem)
    {
        $this->mapaRisco = $mapaRisco;
        $this->titulo = $titulo;
        $this->mensagem = $mensagem;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mapa_risco_id' => $this->mapaRisco->id,
            'titulo' => $this->titulo,
            'mensagem' => $this->mensagem,
            'url' => route('mapas-risco.show', $this->mapaRisco->id),
        ];
    }
}
