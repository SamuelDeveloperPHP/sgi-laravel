<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicAccountBlocked extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cadastro bloqueado')
            ->greeting("Ola, {$notifiable->name}!")
            ->line('Seu cadastro publico temporario foi bloqueado porque o periodo de acesso expirou.')
            ->line('Para continuar usando o SGI, faca um cadastro empresarial com CNPJ e e-mail corporativo.')
            ->line('Esta e uma mensagem automatica de seguranca do SGI QSMS.');
    }
}
