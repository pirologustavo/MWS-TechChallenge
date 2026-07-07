<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

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
        $salt = env("SECRET_KEY");
        $token = md5($this->os->osid . $salt);

        $urlMonolito = env('MONOLITO_URL') . "/os/aprovacao.php?id={$this->os->osid}&token={$token}";

        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                    <h1 style='color: #2c3e50; font-size: 24px;'>Olá! Seu orçamento está pronto para análise.</h1>
                    <p>A sua Ordem de Serviço <strong style='color: #2c3e50;'>#{$this->os->osid}</strong> foi atualizada para o status: <strong style='color: #2980b9;'>Aguardando Aprovação</strong>.</p>
                    <p>Para revisar os itens, valores e tomar uma decisão, clique no botão abaixo para acessar o sistema:</p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$urlMonolito}'
                           style='background-color: #2ecc71; color: white; padding: 12px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                           Visualizar e Responder Orçamento
                        </a>
                    </div>

                    <p style='font-size: 12px; color: #7f8c8d; text-align: center; margin-top: 40px;'>
                        Se o botão acima não funcionar, copie e cole o link a seguir no seu navegador:<br>
                        <a href='{$urlMonolito}' style='color: #3498db;'>{$urlMonolito}</a>
                    </p>
                </div>
            ",
        );
    }
}
