<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegistrationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Company $company,
        private readonly string $decision,
        private readonly ?string $reason = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->decision === 'approved';
        $message = (new MailMessage)
            ->subject($approved ? 'Pré-cadastro aprovado' : 'Atualização do pré-cadastro')
            ->greeting("Olá, {$notifiable->name}!")
            ->line($approved
                ? "O pré-cadastro da empresa {$this->company->nome_fantasia} foi aprovado."
                : "O pré-cadastro da empresa {$this->company->nome_fantasia} foi rejeitado.");

        if ($this->reason) {
            $message->line("Justificativa: {$this->reason}");
        }

        return $message
            ->action($approved ? 'Acessar demonstração' : 'Ver situação do cadastro', url($approved ? '/dashboard' : '/onboarding/pending'))
            ->line('Esta é uma mensagem automática de segurança do SGI QSMS.');
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === 'approved';

        return [
            'company_id' => $this->company->id,
            'titulo' => $approved ? 'Pré-cadastro aprovado' : 'Pré-cadastro rejeitado',
            'mensagem' => $approved
                ? 'Sua empresa foi liberada para acessar a demonstração.'
                : 'Seu pré-cadastro precisa de correções antes da liberação.',
            'reason' => $this->reason,
            'url' => $approved ? route('dashboard') : route('onboarding.pending'),
        ];
    }
}
