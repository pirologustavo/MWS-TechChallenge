<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrcamentoFinalizado extends Mailable
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
            subject: "MWS Oficina - Veículo finalizado! (OS #{$this->os})",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h1>Olá! Seu veículo está pronto</h1>
                <p>A sua Ordem de Serviço foi atualizada para o status: <strong>Finalizado</strong>.</p>
                <p>Por favor, venha retirar seu veículo.</p>
            ",
        );
    }
}
