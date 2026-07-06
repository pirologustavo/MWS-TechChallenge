<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrcamentoAguardandoAprovacao extends Mailable
{
    use Queueable, SerializesModels;

    public $os;

    /**
     * Recebe o objeto/array da OS vindo do seu Service ou Controller
     */
    public function __construct($os)
    {
        $this->os = $os;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "MWS Oficina - Orçamento Pronto para Aprovação (OS #{$this->os->osid})",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h1>Olá! Seu orçamento está pronto para análise.</h1>
                <p>A sua Ordem de Serviço foi atualizada para o status: <strong>Aguardando Aprovação</strong>.</p>
                <p>Por favor, responda a este e-mail ou acesse o link do sistema para aprovar ou recusar a execução dos serviços.</p>
            ",
        );
    }
}
