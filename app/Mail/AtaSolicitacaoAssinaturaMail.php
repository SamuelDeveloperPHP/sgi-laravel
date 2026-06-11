<?php

namespace App\Mail;

use App\Models\AtaReuniao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AtaSolicitacaoAssinaturaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ata;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(AtaReuniao $ata, User $user)
    {
        $this->ata = $ata;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitação de Assinatura: Ata de Reunião - ' . $this->ata->assunto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ata_solicitacao_assinatura',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
