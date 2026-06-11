<?php

namespace App\Mail;

use App\Models\Fornecedor;
use App\Models\FornecedorDocumento;
use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentoReprovadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documento;
    public $fornecedor;
    public $empresa;
    public $userLogado;

    public function __construct(FornecedorDocumento $documento, Fornecedor $fornecedor, Company $empresa, User $userLogado)
    {
        $this->documento = $documento;
        $this->fornecedor = $fornecedor;
        $this->empresa = $empresa;
        $this->userLogado = $userLogado;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aviso: Documento Reprovado - ' . $this->empresa->nome_fantasia,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.documento_reprovado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
